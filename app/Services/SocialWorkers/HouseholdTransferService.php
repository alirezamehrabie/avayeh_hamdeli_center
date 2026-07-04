<?php

namespace App\Services\SocialWorkers;

use App\Models\SocialWorker;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * انتقال خانوارها (سرپرستان) از یک مددکار به مددکار دیگر.
 *
 * تخصیص مددکار روی سرپرست خانوار (guardians.social_worker_id) انجام می‌شود و
 * مددجویان از طریق سرپرست خود مددکار را به ارث می‌برند. بنابراین «انتقال پرونده‌های
 * یک مددکار» یعنی به‌روزرسانی social_worker_id روی سرپرستان مربوطه.
 *
 * سوابق تحویل خدمت (service_deliveries.social_worker_id) عمداً تغییر نمی‌کنند؛ آن‌ها
 * سابقهٔ تاریخی همان مددکاری هستند که خدمت را ارائه کرده است.
 */
class HouseholdTransferService
{
    /**
     * خانوارهای مبدأ را به مددکار مقصد منتقل می‌کند.
     *
     * @param  array<int>|null  $guardianIds  در حالت null همهٔ خانوارهای مبدأ منتقل می‌شوند؛
     *                                         در غیر این صورت فقط خانوارهایی که هم در این لیست
     *                                         و هم متعلق به مددکار مبدأ هستند.
     * @return int  تعداد خانوارهای منتقل‌شده.
     */
    public function transfer(SocialWorker $source, SocialWorker $target, ?array $guardianIds = null): int
    {
        $this->assertTransferable($source, $target);

        return DB::transaction(function () use ($source, $target, $guardianIds): int {
            return $this->moveHouseholds($source, $target, $guardianIds, refreshSource: true);
        });
    }

    /**
     * همهٔ خانوارهای مبدأ را به مقصد منتقل کرده و سپس مددکار مبدأ را غیرفعال می‌کند.
     * انتقال و غیرفعال‌سازی در یک تراکنش انجام می‌شوند تا هیچ خانواری روی مددکار
     * غیرفعال باقی نماند.
     *
     * @return int  تعداد خانوارهای منتقل‌شده.
     */
    public function transferAllAndDeactivate(SocialWorker $source, SocialWorker $target): int
    {
        $this->assertTransferable($source, $target);

        return DB::transaction(function () use ($source, $target): int {
            // مبدأ در حال غیرفعال شدن است؛ نیازی به تازه‌سازی آمار آن نیست.
            $moved = $this->moveHouseholds($source, $target, null, refreshSource: false);

            if (! $source->deactivate()) {
                throw new RuntimeException('غیرفعال‌سازی مددکار مبدأ ناموفق بود.');
            }

            return $moved;
        });
    }

    /**
     * هستهٔ مشترک انتقال؛ باید درون یک تراکنش فراخوانی شود.
     *
     * @param  array<int>|null  $guardianIds
     */
    protected function moveHouseholds(SocialWorker $source, SocialWorker $target, ?array $guardianIds, bool $refreshSource): int
    {
        $query = $source->guardians()->getQuery();

        if ($guardianIds !== null) {
            $normalizedIds = array_values(array_unique(array_filter(
                array_map('intval', $guardianIds),
                static fn (int $id): bool => $id > 0,
            )));

            if ($normalizedIds === []) {
                return 0;
            }

            // فقط خانوارهایی که واقعاً متعلق به مبدأ هستند (جلوگیری از دستکاری ورودی).
            $query->whereIn('id', $normalizedIds);
        }

        // قفل سطرهای انتخاب‌شده تا انتهای تراکنش برای جلوگیری از انتقال هم‌زمان.
        $lockedIds = $query->lockForUpdate()->pluck('id')->all();

        if ($lockedIds === []) {
            return 0;
        }

        $source->guardians()
            ->whereIn('id', $lockedIds)
            ->update(['social_worker_id' => $target->id]);

        if ($refreshSource) {
            $source->refreshStatistics();
        }

        $target->refreshStatistics();

        return count($lockedIds);
    }

    protected function assertTransferable(SocialWorker $source, SocialWorker $target): void
    {
        if ((int) $source->id === (int) $target->id) {
            throw new InvalidArgumentException('مبدأ و مقصد انتقال نمی‌توانند یکسان باشند.');
        }

        if ($source->trashed() || ! $source->is_active) {
            throw new InvalidArgumentException('مددکار مبدأ باید فعال باشد.');
        }

        if ($target->trashed() || ! $target->is_active) {
            throw new InvalidArgumentException('مددکار مقصد باید فعال باشد.');
        }
    }
}
