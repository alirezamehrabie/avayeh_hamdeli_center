<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;

trait HasJalaliBirthDate
{
    /**
     * متولدین یک روز خاص (مثلاً دوم هر ماه)
     */
    public function scopeBornOnDay(Builder $query, int $day): Builder
    {
        return $query->where('birth_day', $day);
    }

    /**
     * متولدین امروز
     */
    public function scopeBirthdayToday(Builder $query): Builder
    {
        $today = Jalalian::now();
        return $query->where('birth_month', $today->getMonth())
            ->where('birth_day', $today->getDay());
    }

    /**
     * متولدین این هفته (۷ روز آینده)
     */
    public function scopeBirthdayThisWeek(Builder $query): Builder
    {
        $today = Jalalian::now();

        return $query->where(function ($q) use ($today) {
            for ($i = 0; $i < 7; $i++) {
                $futureDate = $today->addDays($i);
                $q->orWhere(function ($subQ) use ($futureDate) {
                    $subQ->where('birth_month', $futureDate->getMonth())
                        ->where('birth_day', $futureDate->getDay());
                });
            }
        });
    }

    /**
     * متولدین فردا
     */
    public function scopeBirthdayTomorrow(Builder $query): Builder
    {
        $tomorrow = Jalalian::now()->addDays(1);
        return $query->where('birth_month', $tomorrow->getMonth())
            ->where('birth_day', $tomorrow->getDay());
    }

    /**
     * محاسبه سن دقیق (با در نظر گرفتن ماه و روز)
     */
    public function getExactAgeAttribute(): ?int
    {
        if (!$this->birth_year) {
            return null;
        }

        $today = Jalalian::now();
        $age = $today->getYear() - $this->birth_year;

        // اگر تولد امسال هنوز نرسیده، یک سال کم کن
        if ($today->getMonth() < $this->birth_month ||
            ($today->getMonth() == $this->birth_month && $today->getDay() < $this->birth_day)) {
            $age--;
        }

        return $age;
    }

    /**
     * تاریخ تولد فرمت‌شده فارسی (مثال: ۱۵ دی ۱۳۸۰)
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        if (!$this->birth_year || !$this->birth_month || !$this->birth_day) {
            return null;
        }

        $monthName = self::$months[$this->birth_month] ?? '';
        return $this->birth_day . ' ' . $monthName . ' ' . $this->birth_year;
    }

    /**
     * روزهای باقیمانده تا تولد بعدی
     */
    public function getDaysUntilBirthdayAttribute(): ?int
    {
        if (!$this->birth_month || !$this->birth_day) {
            return null;
        }

        $todayJalali = Jalalian::now();
        $currentYear = $todayJalali->getYear();

        try {
            $birthdayJalali = Jalalian::fromFormat(
                'Y/m/d',
                sprintf('%d/%02d/%02d', $currentYear, $this->birth_month, $this->birth_day)
            );

            if ($birthdayJalali->lessThan($todayJalali)) {
                $birthdayJalali = Jalalian::fromFormat(
                    'Y/m/d',
                    sprintf('%d/%02d/%02d', $currentYear + 1, $this->birth_month, $this->birth_day)
                );
            }

            // ✅ تبدیل به Carbon
            $todayCarbon = $todayJalali->toCarbon();
            $birthdayCarbon = $birthdayJalali->toCarbon();

            return $todayCarbon->diffInDays($birthdayCarbon);

        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * آیا امروز تولدش است؟
     */
    public function getIsBirthdayTodayAttribute(): bool
    {
        $today = Jalalian::now();
        return $this->birth_month == $today->getMonth() &&
            $this->birth_day == $today->getDay();
    }
}
