<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivityServiceDeliverySyncService
{
    private const CHUNK_SIZE = 200;

    public function syncActivity(Activity $activity): void
    {
        DB::transaction(function () use ($activity): void {
            $services = $activity->services()
                ->supportsActivityDelivery()
                ->with(['categories' => fn ($query) => $query->orderBy('id')])
                ->get();

            $activeCategoryIds = $services
                ->flatMap(fn (Service $service) => $service->categories->pluck('id'))
                ->map(fn ($id) => (int) $id)
                ->values();

            $this->removeStaleActivityDeliveries($activity, $activeCategoryIds);

            if ($services->isEmpty() || $activeCategoryIds->isEmpty()) {
                $this->refreshServiceProgress($services);

                return;
            }

            $categoryServiceMap = $services->reduce(function (Collection $map, Service $service): Collection {
                foreach ($service->categories as $category) {
                    $map->put((int) $category->id, $service);
                }

                return $map;
            }, collect());

            $this->restoreExistingActivityDeliveries($activity, $activeCategoryIds);
            $this->createMissingActivityDeliveries($activity, $categoryServiceMap);
            $this->refreshExistingActivityDeliveryValues($activeCategoryIds);
            $this->refreshServiceProgress($services);
        });
    }

    protected function removeStaleActivityDeliveries(Activity $activity, Collection $activeCategoryIds): void
    {
        ServiceDelivery::query()
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->whereHas('activityAttendance', fn ($query) => $query->where('activity_id', $activity->id))
            ->when(
                $activeCategoryIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('service_category_id', $activeCategoryIds->all())
            )
            ->delete();
    }

    protected function restoreExistingActivityDeliveries(Activity $activity, Collection $activeCategoryIds): void
    {
        ServiceDelivery::withTrashed()
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->whereNotNull('deleted_at')
            ->whereIn('service_category_id', $activeCategoryIds->all())
            ->whereHas('activityAttendance', fn ($query) => $query->where('activity_id', $activity->id))
            ->update(['deleted_at' => null]);
    }

    protected function createMissingActivityDeliveries(Activity $activity, Collection $categoryServiceMap): void
    {
        $activity->attendances()
            ->with(['person.guardian:id,guardian_phone_number'])
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($attendances) use ($categoryServiceMap): void {
                $now = now();
                $rows = [];
                $existingPairs = ServiceDelivery::withTrashed()
                    ->whereIn('activity_attendance_id', $attendances->pluck('id')->all())
                    ->whereIn('service_category_id', $categoryServiceMap->keys()->all())
                    ->get(['activity_attendance_id', 'service_category_id'])
                    ->mapWithKeys(fn (ServiceDelivery $delivery): array => [
                        $delivery->activity_attendance_id.'-'.$delivery->service_category_id => true,
                    ]);

                foreach ($attendances as $attendance) {
                    $person = $attendance->person;

                    if (! $person) {
                        continue;
                    }

                    foreach ($categoryServiceMap as $categoryId => $service) {
                        $category = $service->categories->firstWhere('id', (int) $categoryId);

                        if (! $category || $existingPairs->has($attendance->id.'-'.$categoryId)) {
                            continue;
                        }

                        $quantity = 1;
                        $valuePerUnit = (int) ($category->value ?? 0);

                        $rows[] = [
                            'service_id' => $service->id,
                            'service_category_id' => (int) $categoryId,
                            'activity_attendance_id' => $attendance->id,
                            'delivery_channel' => Service::DELIVERY_CHANNEL_ACTIVITY,
                            'social_worker_id' => null,
                            'person_id' => $person->id,
                            'guardian_id' => null,
                            'national_id' => (string) ($person->national_id ?: '0000000000'),
                            'full_name' => $this->personName($person),
                            'mobile' => $person->guardian?->guardian_phone_number,
                            'delivered_quantity' => $quantity,
                            'value_per_unit_snapshot' => $valuePerUnit,
                            'delivered_total_value' => $quantity * $valuePerUnit,
                            'delivered_at' => ($attendance->checked_in_at ?? $now)->toDateString(),
                            'notes' => 'تحویل ثبت‌شده از طریق حضور در فعالیت',
                            'created_by' => auth()->id() ?: $attendance->recorded_by,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    ServiceDelivery::query()->insertOrIgnore($rows);
                }
            });
    }

    protected function refreshExistingActivityDeliveryValues(Collection $activeCategoryIds): void
    {
        ServiceDelivery::query()
            ->where('delivery_channel', Service::DELIVERY_CHANNEL_ACTIVITY)
            ->whereIn('service_category_id', $activeCategoryIds->all())
            ->with('serviceCategory')
            ->chunkById(self::CHUNK_SIZE, function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    $valuePerUnit = (int) ($delivery->serviceCategory?->value ?? 0);

                    $delivery->forceFill([
                        'value_per_unit_snapshot' => $valuePerUnit,
                        'delivered_total_value' => (int) round((float) $delivery->delivered_quantity * $valuePerUnit),
                    ])->saveQuietly();
                }
            });
    }

    protected function refreshServiceProgress(Collection $services): void
    {
        $services->each(fn (Service $service) => $service->refreshDeliveryProgress());
    }

    protected function personName($person): string
    {
        return trim(implode(' ', array_filter([$person->first_name, $person->last_name]))) ?: '-';
    }
}
