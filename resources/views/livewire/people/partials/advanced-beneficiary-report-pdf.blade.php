<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش پیشرفته مددجویان</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 6px; }
        .muted { color: #6b7280; font-size: 11px; }
        .section { margin-top: 14px; }
        .card { display: inline-block; width: 18%; margin: 0 0.5% 8px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; vertical-align: top; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: right; font-size: 11px; }
        th { background: #f3f4f6; font-weight: bold; }
        .chip { display: inline-block; border: 1px solid #d1d5db; border-radius: 999px; padding: 3px 8px; margin: 2px; font-size: 10px; }
    </style>
</head>
<body>
<div class="title">گزارش پیشرفته مددجویان</div>
<div class="muted">تاریخ تولید گزارش: {{ $generatedAt->format('Y/m/d H:i') }}</div>
<div class="muted">جستجوی سراسری: {{ $globalSearch !== '' ? $globalSearch : 'ندارد' }}</div>

<div class="section">
    <div class="card"><div>تعداد کل</div><strong>{{ number_format($totalCount) }}</strong></div>
    <div class="card"><div>تعداد مرد</div><strong>{{ number_format($maleCount) }}</strong></div>
    <div class="card"><div>تعداد زن</div><strong>{{ number_format($femaleCount) }}</strong></div>
    <div class="card"><div>سادات</div><strong>{{ number_format($sadaatCount) }}</strong></div>
    <div class="card"><div>غیر سادات</div><strong>{{ number_format($generalCount) }}</strong></div>
</div>

<div class="section">
    <strong>فیلترهای فعال:</strong>
    <div style="margin-top: 4px;">
        @forelse($activeFilters as $filter)
            <span class="chip">{{ $filter['field'] ?? '-' }}</span>
        @empty
            <span class="muted">فیلتر فعالی ثبت نشده است.</span>
        @endforelse
    </div>
</div>

<div class="section">
    <strong>آمار ماه تولد:</strong>
    <table>
        <thead>
        <tr>
            @foreach($birthMonthStats as $row)
                <th>{{ $row['label'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>
            @foreach($birthMonthStats as $row)
                <td>{{ number_format($row['count']) }}</td>
            @endforeach
        </tr>
        </tbody>
    </table>
</div>

<div class="section">
    <strong>جزئیات مددجویان (حداکثر 1000 رکورد):</strong>
    <table>
        <thead>
        <tr>
            <th>کد مددجو</th>
            <th>نام کامل</th>
            <th>کد ملی</th>
            <th>موبایل</th>
            <th>جنسیت</th>
            <th>سادات</th>
            <th>تاریخ تولد</th>
            <th>تاریخ ثبت</th>
        </tr>
        </thead>
        <tbody>
        @forelse($people as $person)
            <tr>
                <td>{{ $person->person_code }}</td>
                <td>{{ $person->full_name }}</td>
                <td>{{ $person->national_id }}</td>
                <td>{{ $person->phone_number ?: '-' }}</td>
                <td>{{ $person->gender === 'male' ? 'مرد' : ($person->gender === 'female' ? 'زن' : '-') }}</td>
                <td>{{ $person->sadaat_status === 'sadaat' ? 'سادات' : ($person->sadaat_status === 'general' ? 'غیر سادات' : '-') }}</td>
                <td>{{ $person->birth_date ?: '-' }}</td>
                <td>{{ optional($person->created_at)->format('Y/m/d') }}</td>
            </tr>
        @empty
            <tr><td colspan="8">رکوردی یافت نشد.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

</body>
</html>
