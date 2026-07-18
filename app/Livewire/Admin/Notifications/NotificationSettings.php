<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\ManagerNotificationPreference;
use App\Models\User;
use App\Support\Notifications\NotificationEventRegistry;
use Livewire\Component;

class NotificationSettings extends Component
{
    /**
     * ساختار: [form_key => ['enabled' => bool, 'target_type' => string, 'target_roles' => list, 'target_user_ids' => list]]
     * کلید فرم نسخه بدون نقطه event_key است (user.login → user_login) چون
     * wire:model نقطه را به‌عنوان مسیر تو در تو تفسیر می‌کند.
     *
     * @var array<string, array{enabled: bool, target_type: string, target_roles: array, target_user_ids: array}>
     */
    public array $preferences = [];

    public static function formKey(string $eventKey): string
    {
        return str_replace('.', '_', $eventKey);
    }

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-notifications'), 403);

        $saved = ManagerNotificationPreference::query()
            ->where('manager_id', auth()->id())
            ->get()
            ->keyBy('event_key');

        foreach (NotificationEventRegistry::events() as $eventKey => $event) {
            $preference = $saved->get($eventKey);

            $this->preferences[self::formKey($eventKey)] = [
                'enabled' => (bool) ($preference?->enabled ?? false),
                'target_type' => $preference?->target_type ?? NotificationEventRegistry::TARGET_ALL,
                'target_roles' => array_values((array) ($preference?->target_roles ?? [])),
                'target_user_ids' => array_map('strval', array_values((array) ($preference?->target_user_ids ?? []))),
            ];
        }
    }

    public function save(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('manage-notifications'), 403);

        $managerId = auth()->id();

        foreach (NotificationEventRegistry::events() as $eventKey => $event) {
            $formKey = self::formKey($eventKey);
            $input = $this->preferences[$formKey] ?? [];

            $enabled = (bool) ($input['enabled'] ?? false);
            $targetType = (string) ($input['target_type'] ?? NotificationEventRegistry::TARGET_ALL);

            // ورودی‌های سمت کلاینت به مقادیر مجاز محدود می‌شوند.
            if (! $event['supports_targeting'] || ! in_array($targetType, NotificationEventRegistry::targetTypes(), true)) {
                $targetType = NotificationEventRegistry::TARGET_ALL;
            }

            $targetRoles = [];
            $targetUserIds = [];

            if ($targetType === NotificationEventRegistry::TARGET_ROLES) {
                $targetRoles = array_values(array_unique(array_intersect(
                    array_map('strval', (array) ($input['target_roles'] ?? [])),
                    $event['targetable_roles']
                )));

                if ($targetRoles === []) {
                    $this->addError('preferences.'.$formKey, 'برای هدف‌گیری بر اساس نقش، حداقل یک نقش انتخاب کنید.');

                    return;
                }
            }

            if ($targetType === NotificationEventRegistry::TARGET_USERS) {
                $requestedIds = array_values(array_unique(array_map('intval', (array) ($input['target_user_ids'] ?? []))));

                // فقط کاربران واقعی با نقش‌های قابل هدف‌گیری پذیرفته می‌شوند.
                $targetUserIds = User::query()
                    ->whereIn('id', $requestedIds)
                    ->whereIn('access_level', $event['targetable_roles'])
                    ->pluck('id')
                    ->all();

                if ($targetUserIds === []) {
                    $this->addError('preferences.'.$formKey, 'برای هدف‌گیری بر اساس کاربر، حداقل یک کاربر معتبر انتخاب کنید.');

                    return;
                }
            }

            ManagerNotificationPreference::query()->updateOrCreate(
                ['manager_id' => $managerId, 'event_key' => $eventKey],
                [
                    'enabled' => $enabled,
                    'target_type' => $targetType,
                    'target_roles' => $targetRoles ?: null,
                    'target_user_ids' => $targetUserIds ?: null,
                ]
            );

            // فرم را با مقادیر پاک‌سازی‌شده همگام نگه می‌داریم.
            $this->preferences[$formKey] = [
                'enabled' => $enabled,
                'target_type' => $targetType,
                'target_roles' => $targetRoles,
                'target_user_ids' => array_map('strval', $targetUserIds),
            ];
        }

        $this->resetErrorBag();
        $this->dispatch('open-notification-toast', config: [
            'type' => 'success',
            'title' => 'ذخیره شد',
            'message' => 'تنظیمات اعلان‌ها با موفقیت ذخیره شد.',
        ]);
    }

    public function render()
    {
        $events = NotificationEventRegistry::events();

        $targetableRoles = collect($events)
            ->flatMap(fn (array $event): array => $event['targetable_roles'])
            ->unique()
            ->values()
            ->all();

        // کاربران قابل انتخاب برای هدف‌گیری، گروه‌بندی‌شده بر اساس نقش.
        $selectableUsers = User::query()
            ->whereIn('access_level', $targetableRoles)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'name', 'access_level'])
            ->groupBy('access_level');

        $roleLabels = collect(User::roleDefinitions())
            ->map(fn (array $definition): string => $definition['label'])
            ->all();

        return view('livewire.admin.notifications.notification-settings', [
            'events' => $events,
            'selectableUsers' => $selectableUsers,
            'roleLabels' => $roleLabels,
            'targetTypeLabels' => NotificationEventRegistry::targetTypeLabels(),
        ]);
    }
}
