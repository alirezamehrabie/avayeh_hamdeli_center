<?php

namespace App\Services\People;

use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\Person;
use App\Models\Service;
use App\Models\ServiceDelivery;
use Illuminate\Support\Collection;

class BeneficiaryCaseFileTimeline
{
    public function build(
        ?Person $selectedPerson,
        Collection $serviceDeliveries,
        Collection $activityAttendances,
        Collection $caseRecords,
    ): Collection {
        $activityAttendanceIds = $activityAttendances
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $serviceRows = $serviceDeliveries
            ->reject(fn (ServiceDelivery $delivery): bool => $delivery->activity_attendance_id
                && in_array((int) $delivery->activity_attendance_id, $activityAttendanceIds, true))
            ->map(fn (ServiceDelivery $delivery): array => $this->serviceRow($delivery, $selectedPerson));

        $attendanceRows = $activityAttendances
            ->map(fn (ActivityAttendance $attendance): array => $this->attendanceRow($attendance));

        $caseRecordRows = $caseRecords
            ->map(fn (BeneficiaryCaseRecord $record): array => $this->caseRecordRow($record));

        return $serviceRows
            ->toBase()
            ->merge($attendanceRows->toBase())
            ->merge($caseRecordRows->toBase())
            ->sortByDesc('timestamp')
            ->values();
    }

    private function formatDateTime($date): string
    {
        if (! $date) {
            return 'ثبت نشده';
        }

        return Jalalian::fromDateTime($date)->format('Y/m/d H:i');
    }

    private function serviceRow(ServiceDelivery $delivery, ?Person $selectedPerson): array
    {
        $service = $delivery->service;
        $activity = $delivery->activityAttendance?->activity ?? $service?->activity;
        $isFamilyDelivery = $this->isFamilyServiceDelivery($delivery, $selectedPerson);

        return [
            'type' => 'service',
            'date' => $delivery->delivered_at,
            'timestamp' => optional($delivery->delivered_at)->timestamp ?? optional($delivery->created_at)->timestamp ?? 0,
            'title' => $service?->name ?: $service?->serviceName?->name ?: 'خدمت ثبت‌شده',
            'subtitle' => $delivery->serviceCategory?->name,
            'badge' => $isFamilyDelivery ? 'خدمت خانوار' : $this->deliveryChannelLabel($delivery->delivery_channel),
            'quantity' => $this->formatQuantity($delivery),
            'value' => $delivery->delivered_total_value ? number_format((int) $delivery->delivered_total_value).' ریال' : 'ثبت نشده',
            'details' => array_filter([
                'سطح ثبت' => $isFamilyDelivery ? 'خانوار/سرپرست' : 'مددجوی منتخب',
                'دریافت‌کننده' => $delivery->recipient_name,
                'کانال تحویل' => $this->deliveryChannelLabel($delivery->delivery_channel),
                'کد خدمت' => $service?->code,
                'فعالیت مرتبط' => $activity?->name,
                'مددکار/تحویل‌دهنده' => $this->socialWorkerName($delivery),
                'ثبت‌کننده' => $delivery->creator?->name,
                'یادداشت' => $delivery->notes,
            ]),
        ];
    }

    private function attendanceRow(ActivityAttendance $attendance): array
    {
        $activity = $attendance->activity;
        $linkedDeliveries = $attendance->serviceDeliveries;
        $hasLinkedServices = $linkedDeliveries->isNotEmpty();
        $activityServiceDetails = $hasLinkedServices ? $this->activityServiceDetails($linkedDeliveries) : [];

        return [
            'type' => 'activity',
            'date' => $attendance->checked_in_at,
            'timestamp' => optional($attendance->checked_in_at)->timestamp ?? optional($attendance->created_at)->timestamp ?? 0,
            'title' => $activity?->name ?: 'فعالیت ثبت‌شده',
            'subtitle' => $hasLinkedServices
                ? $this->activityServiceSubtitle($linkedDeliveries)
                : ($activity ? (Activity::TYPE_OPTIONS[$activity->activity_type] ?? $activity->activity_type) : null),
            'badge' => ActivityAttendance::STATUS_OPTIONS[$attendance->status] ?? $attendance->status,
            'quantity' => ActivityAttendance::METHOD_OPTIONS[$attendance->registration_method] ?? $attendance->registration_method,
            'value' => $hasLinkedServices
                ? number_format((int) $linkedDeliveries->sum('delivered_total_value')).' ریال'
                : 'فقط فعالیت / فاقد خدمت',
            'details' => array_filter([
                'کد فعالیت' => $activity?->code,
                'مکان' => $activity?->location,
                'زمان ورود' => $this->formatDateTime($attendance->checked_in_at),
                'زمان خروج' => $this->formatDateTime($attendance->checked_out_at),
                'خدمات مرتبط' => $activityServiceDetails['services'] ?? null,
                'جزئیات دسته‌بندی' => $activityServiceDetails['categories'] ?? null,
                'جمع ارزش خدمات' => $activityServiceDetails['total'] ?? null,
                'ثبت‌کننده' => $attendance->recorder?->name,
                'یادداشت' => $attendance->notes,
            ]),
        ];
    }

    private function caseRecordRow(BeneficiaryCaseRecord $record): array
    {
        return [
            'type' => 'manual',
            'record_id' => $record->id,
            'date' => $record->recorded_at,
            'timestamp' => optional($record->recorded_at)->timestamp ?? optional($record->created_at)->timestamp ?? 0,
            'title' => $record->title,
            'subtitle' => $record->description,
            'badge' => $record->record_type_label,
            'quantity' => $record->reference_number ?: 'ثبت دستی',
            'value' => $record->amount !== null ? number_format((int) $record->amount).' ریال' : 'ثبت نشده',
            'details' => array_filter([
                'شماره مرجع' => $record->reference_number,
                'ثبت‌کننده' => $record->creator?->name,
                'پیوست‌ها' => $record->attachments->isNotEmpty() ? number_format($record->attachments->count()).' فایل' : null,
            ]),
            'attachments' => $record->attachments,
        ];
    }

    private function deliveryChannelLabel(?string $channel): string
    {
        return Service::DELIVERY_CHANNEL_OPTIONS[$channel] ?? 'خدمت';
    }

    private function formatQuantity(ServiceDelivery $delivery): string
    {
        $quantity = rtrim(rtrim(number_format((float) $delivery->delivered_quantity, 2, '.', ''), '0'), '.');
        $unit = $delivery->serviceCategory?->unit_label;

        return trim($quantity.' '.($unit ?: 'واحد'));
    }

    private function activityServiceSubtitle(Collection $deliveries): string
    {
        $serviceNames = $deliveries
            ->map(fn (ServiceDelivery $delivery): string => $this->serviceDisplayName($delivery))
            ->filter()
            ->unique()
            ->values();

        if ($serviceNames->isEmpty()) {
            return 'خدمت مرتبط با فعالیت';
        }

        return 'خدمات مرتبط: '.$serviceNames->implode('، ');
    }

    private function activityServiceDetails(Collection $deliveries): array
    {
        $serviceNames = $deliveries
            ->map(fn (ServiceDelivery $delivery): string => $this->serviceDisplayName($delivery))
            ->filter()
            ->unique()
            ->values()
            ->implode('، ');

        $categoryBreakdown = $deliveries
            ->map(function (ServiceDelivery $delivery): string {
                $category = $delivery->serviceCategory;
                $categoryName = $category?->name ?: 'دسته‌بندی ثبت‌شده';
                $quantity = $this->formatQuantity($delivery);
                $unitValue = $delivery->value_per_unit_snapshot !== null
                    ? number_format((int) $delivery->value_per_unit_snapshot).' ریال'
                    : 'ارزش واحد ثبت نشده';
                $totalValue = $delivery->delivered_total_value !== null
                    ? number_format((int) $delivery->delivered_total_value).' ریال'
                    : 'جمع ثبت نشده';

                return "{$categoryName}: {$quantity} × {$unitValue} = {$totalValue}";
            })
            ->implode(' | ');

        return [
            'services' => $serviceNames !== '' ? $serviceNames : null,
            'categories' => $categoryBreakdown !== '' ? $categoryBreakdown : null,
            'total' => number_format((int) $deliveries->sum('delivered_total_value')).' ریال',
        ];
    }

    private function serviceDisplayName(ServiceDelivery $delivery): string
    {
        $service = $delivery->service;

        return $service?->name ?: $service?->serviceName?->name ?: 'خدمت ثبت‌شده';
    }

    private function socialWorkerName(ServiceDelivery $delivery): ?string
    {
        if (! $delivery->socialWorker) {
            return null;
        }

        return trim($delivery->socialWorker->first_name.' '.$delivery->socialWorker->last_name) ?: null;
    }

    private function isFamilyServiceDelivery(ServiceDelivery $delivery, ?Person $selectedPerson): bool
    {
        if (! $selectedPerson) {
            return false;
        }

        if ((int) $delivery->person_id === (int) $selectedPerson->id) {
            return false;
        }

        $selectedGuardianId = (int) ($selectedPerson->guardian_id ?? 0);

        return $selectedGuardianId > 0 && (int) $delivery->guardian_id === $selectedGuardianId;
    }
}
