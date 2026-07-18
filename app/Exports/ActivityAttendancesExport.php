<?php

namespace App\Exports;

use App\Helpers\Morilog\Jalalian;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ActivityAttendancesExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(protected Activity $activity)
    {
    }

    public function query(): Builder
    {
        return ActivityAttendance::query()
            ->with(['person', 'recorder', 'checkOutRecorder'])
            ->where('activity_id', $this->activity->id)
            ->orderBy('checked_in_at')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'نام مددجو',
            'کد مددجو',
            'کد ملی',
            'وضعیت حضور',
            'روش ثبت ورود',
            'زمان ورود',
            'روش ثبت خروج',
            'زمان خروج',
            'ثبت‌کننده ورود',
            'ثبت‌کننده خروج',
            'یادداشت',
        ];
    }

    public function map($row): array
    {
        return [
            $row->person?->full_name ?: trim(($row->person?->first_name ?? '') . ' ' . ($row->person?->last_name ?? '')) ?: '-',
            $row->person?->person_code ?: '-',
            $row->person?->national_id ?: '-',
            ActivityAttendance::STATUS_OPTIONS[$row->status] ?? $row->status,
            ActivityAttendance::METHOD_OPTIONS[$row->registration_method] ?? $row->registration_method,
            $row->checked_in_at ? Jalalian::fromDateTime($row->checked_in_at)->format('Y/m/d H:i') : '-',
            $row->check_out_method ? (ActivityAttendance::METHOD_OPTIONS[$row->check_out_method] ?? $row->check_out_method) : '-',
            $row->checked_out_at ? Jalalian::fromDateTime($row->checked_out_at)->format('Y/m/d H:i') : '-',
            $row->recorder?->full_name ?: $row->recorder?->name ?: '-',
            $row->checkOutRecorder?->full_name ?: $row->checkOutRecorder?->name ?: '-',
            $row->notes ?: '-',
        ];
    }

    public function title(): string
    {
        return 'حضور فعالیت';
    }
}
