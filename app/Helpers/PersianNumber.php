<?php

namespace App\Helpers;

class PersianNumber
{
    public static function toWords(int $number): string
    {
        $number = max(0, $number);

        if ($number === 0) {
            return 'صفر';
        }

        $ones = [
            1 => 'یک',
            2 => 'دو',
            3 => 'سه',
            4 => 'چهار',
            5 => 'پنج',
            6 => 'شش',
            7 => 'هفت',
            8 => 'هشت',
            9 => 'نه',
            10 => 'ده',
            11 => 'یازده',
            12 => 'دوازده',
            13 => 'سیزده',
            14 => 'چهارده',
            15 => 'پانزده',
            16 => 'شانزده',
            17 => 'هفده',
            18 => 'هجده',
            19 => 'نوزده',
        ];

        $tens = [
            2 => 'بیست',
            3 => 'سی',
            4 => 'چهل',
            5 => 'پنجاه',
            6 => 'شصت',
            7 => 'هفتاد',
            8 => 'هشتاد',
            9 => 'نود',
        ];

        $hundreds = [
            1 => 'صد',
            2 => 'دویست',
            3 => 'سیصد',
            4 => 'چهارصد',
            5 => 'پانصد',
            6 => 'ششصد',
            7 => 'هفتصد',
            8 => 'هشتصد',
            9 => 'نهصد',
        ];

        $scales = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون', 'کوادریلیون'];
        $groups = [];

        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $parts = [];

        foreach ($groups as $scaleIndex => $group) {
            if ($group === 0) {
                continue;
            }

            $groupParts = [];
            $hundred = intdiv($group, 100);
            $remainder = $group % 100;

            if ($hundred > 0) {
                $groupParts[] = $hundreds[$hundred];
            }

            if ($remainder > 0) {
                if ($remainder < 20) {
                    $groupParts[] = $ones[$remainder];
                } else {
                    $ten = intdiv($remainder, 10);
                    $one = $remainder % 10;
                    $groupParts[] = $tens[$ten];

                    if ($one > 0) {
                        $groupParts[] = $ones[$one];
                    }
                }
            }

            $part = implode(' و ', $groupParts);

            if ($scales[$scaleIndex] !== '') {
                $part .= ' ' . $scales[$scaleIndex];
            }

            array_unshift($parts, $part);
        }

        return implode(' و ', $parts);
    }

    public static function rialToTomanWords(int $rialAmount): string
    {
        $rialAmount = max(0, $rialAmount);
        $toman = intdiv($rialAmount, 10);
        $remainingRial = $rialAmount % 10;

        $text = self::toWords($toman) . ' تومان';

        if ($remainingRial > 0) {
            $text .= ' و ' . self::toWords($remainingRial) . ' ریال';
        }

        return $text;
    }
}
