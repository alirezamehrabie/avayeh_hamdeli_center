<?php

namespace App\Services\People;

use App\Data\People\BeneficiaryCaseRecordData;
use App\Models\BeneficiaryCaseRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateBeneficiaryCaseRecord
{
    public function __construct(
        private readonly CaseRecordAttachmentStorage $attachmentStorage,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $newAttachments
     * @param  array<int, int>  $removedAttachmentIds
     */
    public function update(
        BeneficiaryCaseRecord $record,
        BeneficiaryCaseRecordData $data,
        array $newAttachments,
        array $removedAttachmentIds,
        int $updatedBy,
    ): BeneficiaryCaseRecord {
        $removedAttachments = $record->attachments()
            ->whereKey($removedAttachmentIds)
            ->get();
        $storedAttachments = collect();

        try {
            DB::transaction(function () use ($record, $data, $newAttachments, $removedAttachmentIds, $updatedBy, &$storedAttachments): void {
                $record->update($data->toAttributes());

                $storedAttachments = $this->attachmentStorage->storeForRecord(
                    $record,
                    $newAttachments,
                    $updatedBy,
                );

                if ($removedAttachmentIds !== []) {
                    $record->attachments()
                        ->whereKey($removedAttachmentIds)
                        ->delete();
                }
            });
        } catch (Throwable $exception) {
            $this->attachmentStorage->deleteFiles($storedAttachments);

            throw $exception;
        }

        $this->attachmentStorage->deleteFiles($removedAttachments);

        return $record->refresh();
    }
}
