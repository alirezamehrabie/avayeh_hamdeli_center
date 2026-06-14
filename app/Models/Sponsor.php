<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sponsor extends Model
{
    use HasFactory;

    public const REMINDER_PHONE = 'phone';
    public const REMINDER_SMS = 'sms';
    public const REMINDER_MESSAGING_APPS = 'messaging_apps';

    protected $fillable = [
        'full_name',
        'monthly_donation_amount',
        'mobile',
        'child_preferences',
        'monthly_payment_reminder_methods',
        'is_social_media_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_donation_amount' => 'integer',
            'monthly_payment_reminder_methods' => 'array',
            'is_social_media_active' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reminderMethodOptions(): array
    {
        return [
            self::REMINDER_PHONE => 'تماس تلفنی',
            self::REMINDER_SMS => 'ارسال پیامک',
            self::REMINDER_MESSAGING_APPS => 'ایتا / واتس اپ',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
