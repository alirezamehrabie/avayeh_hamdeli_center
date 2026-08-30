<?php

namespace App\Support\Notifications;

use App\Models\User;

/**
 * Central registry of manager-notifiable system events. New event types are
 * added here (key + labels + targeting capabilities) without touching the
 * dispatcher, preference UI, or notification center.
 */
class NotificationEventRegistry
{
    public const EVENT_USER_LOGIN = 'user.login';

    public const EVENT_USER_LOGOUT = 'user.logout';

    public const EVENT_SERVICE_CREATED = 'service.created';

    public const EVENT_SERVICE_UPDATED = 'service.updated';

    public const EVENT_SERVICE_ARCHIVED = 'service.archived';

    public const EVENT_SERVICE_RESTORED = 'service.restored';

    public const TARGET_ALL = 'all';

    public const TARGET_ROLES = 'roles';

    public const TARGET_USERS = 'users';

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     group: string,
     *     supports_targeting: bool,
     *     targetable_roles: list<string>
     * }>
     */
    public static function events(): array
    {
        $sessionTargetableRoles = [
            User::ACCESS_LEVEL_ADMIN,
            User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            User::ACCESS_LEVEL_SOCIAL_WORKER,
            User::ACCESS_LEVEL_REGULAR,
            User::ACCESS_LEVEL_ACTIVITY_OPERATOR,
            User::ACCESS_LEVEL_CHILD_SUPPORTER,
        ];

        return [
            self::EVENT_USER_LOGIN => [
                'label' => 'ورود کاربران به سیستم',
                'description' => 'هنگام ورود کاربران انتخاب‌شده (بر اساس نقش یا کاربر مشخص) اعلان دریافت کنید.',
                'group' => 'ورود و خروج',
                'supports_targeting' => true,
                'targetable_roles' => $sessionTargetableRoles,
            ],
            self::EVENT_USER_LOGOUT => [
                'label' => 'خروج کاربران از سیستم',
                'description' => 'هنگام خروج کاربران انتخاب‌شده (بر اساس نقش یا کاربر مشخص) اعلان دریافت کنید.',
                'group' => 'ورود و خروج',
                'supports_targeting' => true,
                'targetable_roles' => $sessionTargetableRoles,
            ],
            self::EVENT_SERVICE_CREATED => [
                'label' => 'ثبت خدمت جدید',
                'description' => 'هنگام ایجاد خدمت جدید توسط اپراتور توزیع یا سایر کاربران اعلان دریافت کنید.',
                'group' => 'خدمات',
                'supports_targeting' => true,
                'targetable_roles' => [
                    User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
                    User::ACCESS_LEVEL_ADMIN,
                ],
            ],
            self::EVENT_SERVICE_UPDATED => [
                'label' => 'ویرایش خدمت',
                'description' => 'هنگام به‌روزرسانی اطلاعات یک خدمت اعلان دریافت کنید.',
                'group' => 'خدمات',
                'supports_targeting' => true,
                'targetable_roles' => [
                    User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
                    User::ACCESS_LEVEL_ADMIN,
                ],
            ],
            self::EVENT_SERVICE_ARCHIVED => [
                'label' => 'غیرفعال‌سازی (آرشیو) خدمت',
                'description' => 'هنگام آرشیو یا غیرفعال شدن یک خدمت اعلان دریافت کنید.',
                'group' => 'خدمات',
                'supports_targeting' => false,
                'targetable_roles' => [],
            ],
            self::EVENT_SERVICE_RESTORED => [
                'label' => 'فعال‌سازی مجدد خدمت',
                'description' => 'هنگام بازگردانی یک خدمت آرشیوشده اعلان دریافت کنید.',
                'group' => 'خدمات',
                'supports_targeting' => false,
                'targetable_roles' => [],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::events());
    }

    public static function has(string $eventKey): bool
    {
        return array_key_exists($eventKey, self::events());
    }

    /**
     * @return array{label: string, description: string, group: string, supports_targeting: bool, targetable_roles: list<string>}|null
     */
    public static function get(string $eventKey): ?array
    {
        return self::events()[$eventKey] ?? null;
    }

    public static function label(string $eventKey): string
    {
        return self::events()[$eventKey]['label'] ?? $eventKey;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::events())
            ->map(fn (array $event): string => $event['label'])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function targetTypes(): array
    {
        return [self::TARGET_ALL, self::TARGET_ROLES, self::TARGET_USERS];
    }

    /**
     * @return array<string, string>
     */
    public static function targetTypeLabels(): array
    {
        return [
            self::TARGET_ALL => 'همه',
            self::TARGET_ROLES => 'نقش‌های انتخاب‌شده',
            self::TARGET_USERS => 'کاربران انتخاب‌شده',
        ];
    }
}
