<?php

namespace App\Services\People;

use App\Data\People\BeneficiaryCaseRecordData;
use App\Models\BeneficiaryCaseRecord;
use App\Models\Person;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateBeneficiaryCaseRecord
{
    public function __construct(
        private readonly CaseRecordAttachmentStorage $attachmentStorage,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function create(
        Person $person,
        int $createdBy,
        BeneficiaryCaseRecordData $data,
        array $attachments = [],
    ): BeneficiaryCaseRecord {
        $storedAttachments = collect();

        try {
            return DB::transaction(function () use ($person, $createdBy, $data, $attachments, &$storedAttachments): BeneficiaryCaseRecord {
                $record = BeneficiaryCaseRecord::query()->create([
                    'person_id' => $person->id,
                    'created_by' => $createdBy,
                    ...$data->toAttributes(),
                ]);

                $storedAttachments = $this->attachmentStorage->storeForRecord(
                    $record,
                    $attachments,
                    $createdBy,
                );

                return $record;
            });
        } catch (Throwable $exception) {
            $this->attachmentStorage->deleteFiles($storedAttachments);

            throw $exception;
        }
    }
}
