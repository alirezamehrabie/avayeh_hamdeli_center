<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'فیلد :attribute باید :digits رقم باشد',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'The :attribute field must be a valid email address.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'فیلد :attribute باید وجود داشته باشد.',
    'present_if' => 'فیلد :attribute زمانی باید وجود داشته باشد که :other برابر :value باشد.',
    'present_unless' => 'فیلد :attribute باید وجود داشته باشد مگر اینکه :other برابر :value باشد.',
    'present_with' => 'فیلد :attribute زمانی باید وجود داشته باشد که :values وجود داشته باشد.',
    'present_with_all' => 'فیلد :attribute زمانی باید وجود داشته باشد که همه :values وجود داشته باشند.',
    'prohibited' => 'فیلد :attribute مجاز نیست.',
    'prohibited_if' => 'فیلد :attribute زمانی مجاز نیست که :other برابر :value باشد.',
    'prohibited_if_accepted' => 'فیلد :attribute زمانی مجاز نیست که :other پذیرفته شده باشد.',
    'prohibited_if_declined' => 'فیلد :attribute زمانی مجاز نیست که :other رد شده باشد.',
    'prohibited_unless' => 'فیلد :attribute مجاز نیست مگر اینکه :other در میان :values باشد.',
    'prohibits' => 'وجود فیلد :attribute مانع از وجود فیلد :other می‌شود.',
    'regex' => 'فرمت فیلد :attribute نامعتبر است.',
    'required' => 'فیلد :attribute ضروری است',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'فیلد :attribute زمانی الزامی است که :other برابر :value باشد.',
    'required_if_accepted' => 'فیلد :attribute زمانی الزامی است که :other پذیرفته شده باشد.',
    'required_if_declined' => 'فیلد :attribute زمانی الزامی است که :other رد شده باشد.',
    'required_unless' => 'فیلد :attribute الزامی است مگر اینکه :other در میان :values باشد.',
    'required_with' => 'فیلد :attribute زمانی الزامی است که :values وجود داشته باشد.',
    'required_with_all' => 'فیلد :attribute زمانی الزامی است که همه :values وجود داشته باشند.',
    'required_without' => 'فیلد :attribute زمانی الزامی است که :values وجود نداشته باشد.',
    'required_without_all' => 'فیلد :attribute زمانی الزامی است که هیچ‌کدام از :values وجود نداشته باشند.',
    'same' => 'فیلد :attribute باید با :other مطابقت داشته باشد.',
    'size' => [
        'array' => 'فیلد :attribute باید شامل :size آیتم باشد.',
        'file' => 'حجم فایل :attribute باید :size کیلوبایت باشد.',
        'numeric' => 'مقدار :attribute باید برابر :size باشد.',
        'string' => 'فیلد :attribute باید :size کاراکتر باشد.',
    ],

    'starts_with' => 'فیلد :attribute باید با یکی از مقادیر زیر شروع شود: :values.',
    'string' => 'فیلد :attribute باید یک رشته باشد.',
    'timezone' => 'فیلد :attribute باید یک منطقه زمانی معتبر باشد.',
    'unique' => 'مقدار :attribute قبلاً ثبت شده است.',
    'uploaded' => 'آپلود :attribute با خطا مواجه شد.',
    'uppercase' => 'فیلد :attribute باید با حروف بزرگ باشد.',
    'url' => 'فیلد :attribute باید یک آدرس URL معتبر باشد.',
    'ulid' => 'فیلد :attribute باید یک ULID معتبر باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد.',


    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */


    'attributes' => [
        'username','email' => 'نام کاربری',
        'password' => 'رمز عبور',
        'national_id'                   => 'کد ملی',
        'first_name'                    => 'نام مددجو',
        'last_name'                     => 'نام خانوادگی مددجو',
        'birth_day'                     => 'روز تولد مددجو',
        'birth_month'                   => 'ماه تولد مددجو',
        'birth_year'                    => 'سال تولد مددجو',
        'father_name'                   => 'نام پدر مددجو',
        'gender'                        => 'جنسیت مددجو',
        'phone_number' => 'تلفن همراه',
        'mother_national_id' => 'کد ملی مادر',
        'father_national_id' => 'کد ملی پدر',
        'sadaat_relation_id' => 'نسب سادات',
        'sadaat_status' => 'وضعیت سادات',
        'has_disability' => 'وضعیت معلولیت',
        'disability_type_id' => 'نوع معلولیت',
        'social_worker_id'              => 'شناسه مددکار اجتماعی',
        'guardian_relation_type_id'     => 'شناسه نوع رابطه سرپرست',
        'guardian_national_code'        => 'کد ملی سرپرست',
        'guardian_first_name'           => 'نام سرپرست',
        'guardian_last_name'            => 'نام خانوادگی سرپرست',
        'guardian_birth_day'            => 'روز تولد سرپرست',
        'guardian_birth_month'          => 'ماه تولد سرپرست',
        'guardian_birth_year'           => 'سال تولد سرپرست',
        'occupation_id'                 => 'شناسه شغل مددجو',
        'any_family_employed'           => 'اشتغال یکی از اعضای خانواده',
        'residence_status_id'           => 'شناسه وضعیت سکونت',
        'address'                       => 'آدرس',
        'personal_phone'                => 'تلفن همراه',
        'account_owner_relation_id'     => 'شناسه نسبت مالک حساب',
        'need_level_id'                 => 'شناسه سطح نیاز',
        'shenasnameh_serial' => 'سریال شناسنامه',
        'shenasnameh_series_number' => 'سری عددی شناسنامه',
        'shenasnameh_series_letter' => 'حرف سری شناسنامه',
        'harm_types' => 'نوع آسیب مددجو',
        'education_degree' => 'مدرک تحصیلی',
        'any_family_employed_description' => 'توضیحات اعضای شاغل',
        'has_vehicle' => 'وسیله نقلیه',
        'vehicle_type_id' => 'نوع وسیله نقلیه',
        'vehicle_ownership_type' => 'نوع مالکیت وسیله نقلیه',
        'reason_for_not_studying' => 'علت عدم تحصیل'

    ],

    'values' => [
        'sadaat_status' => [
            'general' => 'عام',
            'sadaat' => 'سادات',
        ],
        'any_family_employed' => [
            '0' => 'خیر',
            '1' => 'بله',
        ],
    ],

];
