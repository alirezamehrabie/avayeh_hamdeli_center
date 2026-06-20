<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class SponsorProfile extends Model
{
    use HasFactory;

    public const REMINDER_PHONE = 'phone';
    public const REMINDER_SMS = 'sms';
    public const REMINDER_MESSAGING_APPS = 'messaging_apps';

    protected $fillable = [
        'user_id',
        'supporter_code',
        'monthly_donation_amount',
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

    public static function generateNextSupporterCode(): string
    {
        return DB::transaction(function (): string {
            $lastCode = self::query()
                ->whereNotNull('supporter_code')
                ->where('supporter_code', 'like', 'CS-%')
                ->lockForUpdate()
                ->max('supporter_code');

            $lastSequence = 0;

            if (is_string($lastCode) && preg_match('/^CS-(\d+)$/', $lastCode, $matches) === 1) {
                $lastSequence = (int) $matches[1];
            }

            return sprintf('CS-%04d', $lastSequence + 1);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function beneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_sponsor_profile')
            ->withTimestamps();
    }
}
