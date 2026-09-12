<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardReminder extends Model
{
    use HasFactory;

    /**
     * دسته‌بندی‌های یادآوری
     * کلید: مقدار ذخیره‌شده در دیتابیس
     * مقدار: برچسب فارسی (منبع یکتای فرم افزودن و نمایش لیست)
     */
    public static array $categories = [
        'today_tasks' => 'کارهای امروز',
        'pending_approvals' => 'موارد در انتظار تایید',
        'contract_deadlines' => 'سررسید قراردادها',
        'required_reports' => 'گزارش‌های مورد نیاز',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'is_done',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];
}
