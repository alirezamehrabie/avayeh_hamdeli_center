<?php

namespace App\Services\Announcements;

use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateMandatoryAnnouncementService
{
    /**
     * @param  array{
     *     title: string,
     *     body: string,
     *     priority?: string,
     *     target_type?: string,
     *     social_worker_ids?: array<int>,
     *     is_blocking?: bool,
     *     min_reading_seconds?: int,
     *     reset_acknowledgments?: bool
     * }  $data
     */
    public function update(MandatoryAnnouncement $announcement, array $data): MandatoryAnnouncement
    {
        return DB::transaction(function () use ($announcement, $data): MandatoryAnnouncement {
            $targetType = $data['target_type'] ?? $announcement->target_type;

            $announcement->update([
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                'priority' => $data['priority'] ?? $announcement->priority,
                'target_type' => $targetType,
                'is_blocking' => (bool) ($data['is_blocking'] ?? $announcement->is_blocking),
                'min_reading_seconds' => (int) ($data['min_reading_seconds'] ?? $announcement->min_reading_seconds),
            ]);

            $recipientUsers = $this->resolveRecipientUsers($targetType, $data['social_worker_ids'] ?? []);
            $targetUserIds = $recipientUsers->pluck('id')->all();

            // 1. Remove recipients no longer in target list
            $announcement->recipients()
                ->whereNotIn('user_id', $targetUserIds)
                ->delete();

            // 2. Add newly added recipients that do not exist yet
            $existingUserIds = $announcement->recipients()
                ->pluck('user_id')
                ->all();

            $newRecipients = $recipientUsers
                ->reject(fn (User $user) => in_array($user->id, $existingUserIds, true))
                ->map(fn (User $user) => [
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                    'social_worker_id' => $user->social_worker_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

            if (! empty($newRecipients)) {
                MandatoryAnnouncementRecipient::query()->insert($newRecipients);
            }

            // 3. Reset acknowledgments if requested
            if (! empty($data['reset_acknowledgments'])) {
                $announcement->recipients()->update([
                    'read_at' => null,
                    'ip_address' => null,
                    'user_agent' => null,
                    'updated_at' => now(),
                ]);
            }

            return $announcement->fresh(['recipients']);
        });
    }

    /**
     * @param  array<int>  $specificSocialWorkerIds
     * @return Collection<int, User>
     */
    public function resolveRecipientUsers(string $targetType, array $specificSocialWorkerIds = []): Collection
    {
        if ($targetType === MandatoryAnnouncement::TARGET_SPECIFIC_SOCIAL_WORKERS && ! empty($specificSocialWorkerIds)) {
            return User::query()
                ->whereIn('social_worker_id', $specificSocialWorkerIds)
                ->get();
        }

        return User::query()
            ->where(function ($query): void {
                $query->where('access_level', User::ACCESS_LEVEL_SOCIAL_WORKER)
                    ->orWhereNotNull('social_worker_id');
            })
            ->where(function ($query): void {
                $query->whereDoesntHave('socialWorker')
                    ->orWhereHas('socialWorker', fn ($q) => $q->where('is_active', true));
            })
            ->get();
    }
}
