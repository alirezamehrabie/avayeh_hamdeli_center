<?php

namespace App\Services\Announcements;

use App\Models\MandatoryAnnouncement;
use App\Models\MandatoryAnnouncementRecipient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateMandatoryAnnouncementService
{
    /**
     * @param  array{
     *     title: string,
     *     body: string,
     *     priority?: string,
     *     target_type?: string,
     *     social_worker_ids?: array<int>,
     *     is_blocking?: bool,
     *     min_reading_seconds?: int
     * }  $data
     */
    public function create(array $data, ?User $creator = null): MandatoryAnnouncement
    {
        return DB::transaction(function () use ($data, $creator): MandatoryAnnouncement {
            $targetType = $data['target_type'] ?? MandatoryAnnouncement::TARGET_ALL_SOCIAL_WORKERS;

            $announcement = MandatoryAnnouncement::query()->create([
                'created_by' => $creator?->id ?? auth()->id(),
                'title' => trim($data['title']),
                'body' => trim($data['body']),
                'priority' => $data['priority'] ?? MandatoryAnnouncement::PRIORITY_IMPORTANT,
                'target_type' => $targetType,
                'is_blocking' => (bool) ($data['is_blocking'] ?? true),
                'min_reading_seconds' => (int) ($data['min_reading_seconds'] ?? 0),
            ]);

            $recipientUsers = $this->resolveRecipientUsers($targetType, $data['social_worker_ids'] ?? []);

            $recipientsPayload = $recipientUsers->map(fn (User $user) => [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'social_worker_id' => $user->social_worker_id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($recipientsPayload)) {
                MandatoryAnnouncementRecipient::query()->insert($recipientsPayload);
            }

            return $announcement->fresh(['recipients']);
        });
    }

    /**
     * @param  array<int>  $specificSocialWorkerIds
     * @return Collection<int, User>
     */
    protected function resolveRecipientUsers(string $targetType, array $specificSocialWorkerIds = []): Collection
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
