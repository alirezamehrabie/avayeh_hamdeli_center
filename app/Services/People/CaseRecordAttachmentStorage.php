<?php

namespace App\Services\People;

use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CaseRecordAttachmentStorage
{
    /**
     * پیوست‌های پرونده مددجو، اسناد حساس شخصی هستند و باید روی دیسک خصوصی
     * (خارج از public/storage) ذخیره شوند و فقط از طریق روت کنترل‌شده‌ی
     * admin.people.case-file.attachments.show سرو شوند.
     * رکوردهای قدیمی با disk='public' همچنان از همان مسیر قبلی سرو می‌شوند.
     */
    private const TARGET_DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @return Collection<int, BeneficiaryCaseRecordAttachment>
     */
    public function storeForRecord(
        BeneficiaryCaseRecord $record,
        array $uploads,
        int $uploadedBy,
    ): Collection {
        $storedAttachments = collect();
        $storedPaths = [];

        try {
            foreach ($uploads as $upload) {
                $path = $upload->store(
                    "beneficiary-case-records/{$record->person_id}/{$record->id}",
                    self::TARGET_DISK
                );
                $storedPaths[] = $path;

                $storedAttachments->push(BeneficiaryCaseRecordAttachment::query()->create([
                    'beneficiary_case_record_id' => $record->id,
                    'uploaded_by' => $uploadedBy,
                    'disk' => self::TARGET_DISK,
                    'path' => $path,
                    'original_name' => $upload->getClientOriginalName(),
                    'mime_type' => $upload->getMimeType(),
                    'size' => $upload->getSize(),
                ]));
            }
        } catch (Throwable $exception) {
            Storage::disk(self::TARGET_DISK)->delete($storedPaths);

            throw $exception;
        }

        return $storedAttachments;
    }

    /**
     * @param  iterable<BeneficiaryCaseRecordAttachment>  $attachments
     */
    public function deleteFiles(iterable $attachments): void
    {
        foreach ($attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
    }
}
