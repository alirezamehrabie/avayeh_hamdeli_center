<?php

return [
    'definition' => [
        'messages' => [
            'saved' => 'فعالیت با موفقیت ذخیره شد.',
        ],
        'validation' => [
            'attributes' => [
                'name' => 'نام فعالیت',
                'activityType' => 'نوع فعالیت',
                'description' => 'توضیحات',
                'location' => 'مکان',
                'startsAt' => 'زمان شروع',
                'endsAt' => 'زمان پایان',
                'capacity' => 'ظرفیت',
                'statusNotes' => 'یادداشت وضعیت',
            ],
            'messages' => [
                'end_requires_start' => 'برای ثبت زمان پایان، زمان شروع نیز الزامی است.',
                'end_after_start' => 'زمان پایان باید بعد از زمان شروع باشد.',
                'invalid_jalali_datetime' => ':attribute شمسی معتبر نیست. قالب درست: 1403/01/01 یا 1403/01/01 14:30',
            ],
        ],
    ],
];
