<?php

namespace App\Traits;

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
