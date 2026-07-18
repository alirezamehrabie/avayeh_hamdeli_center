<?php

namespace App\Services\Deliveries;

use App\Helpers\PersianText;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceDelivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Records a direct final service delivery (no social-worker intermediary)
 * in the canonical ServiceDelivery ledger.
 *
 * Shared rules with the social-worker workflow:
 *  - per-category stock is validated inside a transaction with row locks,
 *  - every row carries value snapshots, audit metadata and a shared
 *    delivery_batch_id (the batch UUID doubles as the tracking reference).
 */
class DirectDeliveryRecorder
{
    /**
     * @param  array<int, float>  $categoryQuantities  category id => quantity
     * @param  array{person_id?: int|null, guardian_id?: int|null, national_id: string, full_name: string, mobile?: string|null}  $recipient
     * @return array{batch_id: string, tracking_code: string, deliveries: Collection<int, ServiceDelivery>}
     *
     * @throws ValidationException
     */
    public function record(
        Service $service,
        array $recipient,
        array $categoryQuantities,
        string $deliveredAt,
        ?string $notes,
        int $createdBy,
    ): array {
        $categoryQuantities = collect($categoryQuantities)
            ->mapWithKeys(fn ($quantity, $categoryId) => [(int) $categoryId => (float) $quantity])
            ->filter(fn (float $quantity, int $categoryId) => $categoryId > 0 && $quantity > 0);

        if ($categoryQuantities->isEmpty()) {
            throw ValidationException::withMessages([
                'categoryQuantities' => 'حداقل یک مقدار تحویل برای دسته‌بندی‌های خدمت وارد کنید.',
            ]);
        }

        $batchId = (string) Str::uuid();

        $deliveries = DB::transaction(function () use ($service, $recipient, $categoryQuantities, $deliveredAt, $notes, $createdBy, $batchId): Collection {
            $categoryIds = $categoryQuantities->keys()->all();

            // Row locks serialize concurrent submissions against the same
            // categories so the stock check below cannot be raced.
            $lockedCategories = ServiceCategory::query()
                ->where('service_id', $service->id)
                ->whereIn('id', $categoryIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $deliveredByCategory = ServiceDelivery::query()
                ->select('service_category_id')
                ->selectRaw('COALESCE(SUM(delivered_quantity), 0) as delivered_quantity')
                ->where('service_id', $service->id)
                ->whereIn('service_category_id', $categoryIds)
                ->groupBy('service_category_id')
                ->pluck('delivered_quantity', 'service_category_id');

            foreach ($categoryQuantities as $categoryId => $quantity) {
                $category = $lockedCategories->get($categoryId);

                if (! $category) {
                    throw ValidationException::withMessages([
                        'categoryQuantities' => 'دسته‌بندی انتخاب‌شده متعلق به این خدمت نیست.',
                    ]);
                }

                $remainingStock = max(0, (float) $category->quantity - (float) ($deliveredByCategory[$categoryId] ?? 0));

                if ($quantity > $remainingStock) {
                    throw ValidationException::withMessages([
                        'categoryQuantities' => "مقدار تحویل «{$category->name}» از موجودی باقی‌مانده همان دسته‌بندی بیشتر است.",
                    ]);
                }
            }

            return $categoryQuantities->map(function (float $quantity, int $categoryId) use ($service, $recipient, $deliveredAt, $notes, $createdBy, $batchId, $lockedCategories): ServiceDelivery {
                $category = $lockedCategories->get($categoryId);
                $unitValue = (int) ($category?->value ?? 0);

                return ServiceDelivery::query()->create([
                    'delivery_batch_id' => $batchId,
                    'service_id' => $service->id,
                    'service_category_id' => $categoryId,
                    'delivery_channel' => Service::DELIVERY_CHANNEL_DIRECT,
                    'social_worker_id' => null,
                    'person_id' => $recipient['person_id'] ?? null,
                    'guardian_id' => $recipient['guardian_id'] ?? null,
                    'national_id' => (string) $recipient['national_id'],
                    'full_name' => (string) $recipient['full_name'],
                    'mobile' => filled($recipient['mobile'] ?? null) ? (string) $recipient['mobile'] : null,
                    'delivered_quantity' => $quantity,
                    'value_per_unit_snapshot' => $unitValue,
                    'delivered_total_value' => (int) round($quantity * $unitValue),
                    'delivered_at' => $deliveredAt,
                    'notes' => $notes ?: null,
                    'created_by' => $createdBy,
                ]);
            })->values();
        });

        return [
            'batch_id' => $batchId,
            'tracking_code' => self::trackingCode($batchId),
            'deliveries' => $deliveries,
        ];
    }

    /**
     * Resolve the recipient for a direct delivery: an existing Person for
     * individual services, an existing Guardian for family services, or a
     * validated manual entry when no registered record matches.
     *
     * Existing records are re-resolved server-side by id (never trusting a
     * client-side snapshot) and manual entries are re-checked against the
     * database so a matching registered record is always linked instead of
     * duplicated.
     *
     * @return array{person_id: int|null, guardian_id: int|null, national_id: string, full_name: string, mobile: string|null, is_manual: bool}
     *
     * @throws ValidationException
     */
    public function resolveRecipient(
        Service $service,
        ?int $personId,
        ?int $guardianId,
        string $manualNationalId,
        string $manualFullName,
        ?string $manualMobile,
    ): array {
        if ($service->service_type === 'family') {
            if ($guardianId) {
                $guardian = Guardian::query()->find($guardianId);

                if (! $guardian) {
                    throw ValidationException::withMessages([
                        'recipient' => 'سرپرست انتخاب‌شده یافت نشد یا غیرفعال است.',
                    ]);
                }

                return [
                    'person_id' => null,
                    'guardian_id' => (int) $guardian->id,
                    'national_id' => (string) ($guardian->national_code ?: '0000000000'),
                    'full_name' => $guardian->full_name !== '' ? $guardian->full_name : '-',
                    'mobile' => $guardian->guardian_phone_number ?: null,
                    'is_manual' => false,
                ];
            }

            $manual = $this->validatedManualEntry($manualNationalId, $manualFullName, $manualMobile);

            // Never create a duplicate manual record for an already-registered guardian.
            $registered = Guardian::query()->where('national_code', $manual['national_id'])->first();

            if ($registered) {
                return [
                    'person_id' => null,
                    'guardian_id' => (int) $registered->id,
                    'national_id' => (string) $registered->national_code,
                    'full_name' => $registered->full_name !== '' ? $registered->full_name : $manual['full_name'],
                    'mobile' => $registered->guardian_phone_number ?: $manual['mobile'],
                    'is_manual' => false,
                ];
            }

            return $manual + ['person_id' => null, 'guardian_id' => null, 'is_manual' => true];
        }

        if ($personId) {
            $person = Person::query()->with('guardian:id,guardian_phone_number')->find($personId);

            if (! $person) {
                throw ValidationException::withMessages([
                    'recipient' => 'مددجوی انتخاب‌شده یافت نشد یا غیرفعال است.',
                ]);
            }

            return [
                'person_id' => (int) $person->id,
                'guardian_id' => null,
                'national_id' => (string) ($person->national_id ?: '0000000000'),
                'full_name' => trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: '-',
                'mobile' => ($person->phone_number ?: $person->guardian?->guardian_phone_number) ?: null,
                'is_manual' => false,
            ];
        }

        $manual = $this->validatedManualEntry($manualNationalId, $manualFullName, $manualMobile);

        $registered = Person::query()->where('national_id', $manual['national_id'])->first();

        if ($registered) {
            return [
                'person_id' => (int) $registered->id,
                'guardian_id' => null,
                'national_id' => (string) $registered->national_id,
                'full_name' => trim(($registered->first_name ?? '').' '.($registered->last_name ?? '')) ?: $manual['full_name'],
                'mobile' => $manual['mobile'],
                'is_manual' => false,
            ];
        }

        return $manual + ['person_id' => null, 'guardian_id' => null, 'is_manual' => true];
    }

    /**
     * Human-friendly reference derived from the canonical batch UUID.
     */
    public static function trackingCode(string $batchId): string
    {
        return 'DL-'.strtoupper(substr(str_replace('-', '', $batchId), 0, 8));
    }

    /**
     * @return array{national_id: string, full_name: string, mobile: string|null}
     *
     * @throws ValidationException
     */
    protected function validatedManualEntry(string $nationalId, string $fullName, ?string $mobile): array
    {
        $nationalId = PersianText::digitsOnly($nationalId);
        $fullName = PersianText::normalizeText($fullName);
        $mobile = PersianText::digitsOnly((string) $mobile);

        $errors = [];

        if (! preg_match('/^\d{10}$/', $nationalId)) {
            $errors['manualNationalId'] = 'کد ملی گیرنده باید ۱۰ رقم باشد.';
        }

        if ($fullName === '') {
            $errors['manualFullName'] = 'نام و نام خانوادگی گیرنده الزامی است.';
        }

        if ($mobile !== '' && ! preg_match('/^09\d{9}$/', $mobile)) {
            $errors['manualMobile'] = 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'national_id' => $nationalId,
            'full_name' => $fullName,
            'mobile' => $mobile !== '' ? $mobile : null,
        ];
    }
}
