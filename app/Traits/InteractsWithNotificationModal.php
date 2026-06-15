<?php

namespace App\Traits;

use Illuminate\Support\Arr;

trait InteractsWithNotificationModal
{
    protected function openNotificationModal(array $config): void
    {
        $this->dispatch('open-notification-modal', config: $this->normalizeNotificationModalConfig($config));
    }

    protected function closeNotificationModal(): void
    {
        $this->dispatch('close-notification-modal');
    }

    protected function openValidationErrorModal(array $messages, string $title = 'لطفاً خطاهای فرم را برطرف کنید'): void
    {
        $errors = collect(Arr::flatten($messages))
            ->map(fn ($message) => trim((string) $message))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->openNotificationModal([
            'type' => 'error',
            'title' => $title,
            'message' => $errors === []
                ? 'ورودی‌های فرم معتبر نیستند. لطفاً موارد مشخص‌شده را بررسی کنید.'
                : implode(PHP_EOL, array_map(fn (string $message) => '• ' . $message, $errors)),
            'icon' => 'error',
            'buttons' => [
                [
                    'label' => 'اصلاح اطلاعات',
                    'action' => 'close',
                    'variant' => 'danger',
                ],
            ],
        ]);
    }

    protected function openSystemErrorModal(string $message = 'در ثبت اطلاعات مشکلی رخ داد. لطفاً دوباره تلاش کنید.', ?string $retryEvent = null): void
    {
        $buttons = [[
            'label' => 'بستن',
            'action' => 'close',
            'variant' => 'secondary',
        ]];

        if ($retryEvent) {
            array_unshift($buttons, [
                'label' => 'تلاش دوباره',
                'action' => 'event',
                'event' => $retryEvent,
                'variant' => 'danger',
            ]);
        }

        $this->openNotificationModal([
            'type' => 'error',
            'title' => 'خطای سیستمی',
            'message' => $message,
            'icon' => 'error',
            'buttons' => $buttons,
        ]);
    }

    protected function normalizeNotificationModalConfig(array $config): array
    {
        $type = in_array($config['type'] ?? 'info', ['success', 'warning', 'error', 'info'], true)
            ? $config['type']
            : 'info';

        $template = $config['template'] ?? 'default';
        $buttons = $config['buttons'] ?? [];

        if ($buttons === []) {
            $buttons = [[
                'label' => 'متوجه شدم',
                'action' => 'close',
                'variant' => $type === 'error' ? 'danger' : 'primary',
            ]];
        }

        return [
            'type' => $type,
            'template' => $template,
            'title' => (string) ($config['title'] ?? $this->defaultNotificationTitle($type)),
            'message' => (string) ($config['message'] ?? ''),
            'icon' => $config['icon'] ?? $type,
            'closable' => (bool) ($config['closable'] ?? true),
            'buttons' => array_map(function (array $button): array {
                return [
                    'label' => (string) ($button['label'] ?? 'متوجه شدم'),
                    'action' => $button['action'] ?? 'close',
                    'variant' => $button['variant'] ?? 'primary',
                    'href' => $button['href'] ?? null,
                    'event' => $button['event'] ?? null,
                    'payload' => $button['payload'] ?? [],
                    'new_tab' => (bool) ($button['new_tab'] ?? false),
                ];
            }, $buttons),
            'meta' => is_array($config['meta'] ?? null) ? $config['meta'] : [],
        ];
    }

    protected function defaultNotificationTitle(string $type): string
    {
        return match ($type) {
            'success' => 'عملیات با موفقیت انجام شد',
            'warning' => 'هشدار',
            'error' => 'خطا',
            default => 'اطلاع‌رسانی',
        };
    }
}
