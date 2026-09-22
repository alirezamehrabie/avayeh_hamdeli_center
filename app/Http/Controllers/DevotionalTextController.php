<?php

namespace App\Http\Controllers;

class DevotionalTextController extends Controller
{
    /**
     * صفحۀ متن زیارت/دعا از بخش «ارتباط با خدا» مجله.
     *
     * @param  string  $file  نام فایل متن در resources/text
     * @param  string  $title  عنوان صفحۀ متن و برند هدر
     * @param  string  $description  خط توضیح زیر عنوان
     */
    public function show(string $file, string $title, string $description)
    {
        // فایل‌ها در ویندوز ساخته شده‌اند؛ خط‌پایان‌ها یکدست می‌شوند.
        // از str_replace استفاده می‌شود چون preg با \R بدون پرچم /u بایت‌های میانیِ UTF-8 عربی را خراب می‌کند.
        $raw = trim(str_replace(["\r\n", "\r"], "\n", file_get_contents(resource_path("text/{$file}"))));

        // پاراگراف‌ها با خط خالی جدا می‌شوند؛ شمارۀ پاورقی انتهای هر پاراگراف حذف می‌شود
        $paragraphs = array_values(array_filter(array_map(
            fn (string $paragraph): array => $this->parseParagraph($paragraph),
            preg_split('/\n\s*\n/', $raw) ?: [],
        ), fn (array $p): bool => $p['text'] !== '' || $p['instruction'] !== null));

        return view('magazine.devotional-text', [
            'title' => $title,
            'description' => $description,
            'paragraphs' => $paragraphs,
        ]);
    }

    /**
     * @return array{instruction: ?string, text: string}
     */
    private function parseParagraph(string $paragraph): array
    {
        // شمارۀ پاورقی در انتهای پاراگراف و هر خطِ صرفاًعددی (شمارۀ جا‌مانده از کپی) حذف می‌شود
        $text = preg_replace('/\s+\d+\s*$/u', '', trim($paragraph)) ?? '';
        $text = trim(preg_replace('/^\h*\d+\h*$/mu', '', $text) ?? '');

        // دستورالعملِ خواندن فارسی از متن عربی جدا می‌شود:
        // یا سطر مستقلِ ختم‌شونده به «:» (دعای توسل)، یا پیشوند «… می گویی :» (زیارت عاشورا)
        if (preg_match('/^([^\n]*:)\s*\n(.*)$/su', $text, $m)) {
            return ['instruction' => trim($m[1]), 'text' => trim($m[2])];
        }

        if (preg_match('/^(.+?گویی\s*:)\s*(.*)$/su', $text, $m)) {
            return ['instruction' => trim($m[1]), 'text' => trim($m[2])];
        }

        return ['instruction' => null, 'text' => trim($text)];
    }
}
