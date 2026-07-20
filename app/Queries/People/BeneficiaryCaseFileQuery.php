<?php

namespace App\Queries\People;

use App\Models\ActivityAttendance;
use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use App\Models\Person;
use App\Models\ServiceDelivery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BeneficiaryCaseFileQuery
{
    public function __construct(
        private readonly PeopleIndexSearchQuery $peopleSearch,
    ) {}

    public function searchPeople(string $search): Collection
    {
        $search = Person::normalizeSearchText($search);

        if (mb_strlen($search) < 2 && ! ctype_digit($search)) {
            return collect();
        }

        $query = Person::query()
            ->select([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'full_name',
                'national_id',
                'birth_day',
                'birth_month',
                'birth_year',
                'guardian_id',
                'created_at',
            ])
            ->with(['guardian:id,first_name,last_name,national_code,social_worker_id'])
            ->orderByDesc('created_at');

        $this->peopleSearch->applyTo($query, $search);

        return $query
            ->limit(8)
            ->get();
    }

    public function findPerson(?int $personId): ?Person
    {
        if (! $personId) {
            return null;
        }

        return Person::query()
            ->with([
                'guardian:id,first_name,last_name,national_code,guardian_phone_number,social_worker_id',
                'guardian.socialWorker:id,first_name,last_name,worker_code',
            ])
            ->find($personId);
    }

    public function findQrPerson(int $personId): ?Person
    {
        return Person::query()->find($personId);
    }

    public function serviceDeliveries(Person $person): Collection
    {
        return ServiceDelivery::query()
            ->with([
                'service:id,code,name,service_name_id,service_type,activity_id',
                'service.serviceName:id,name',
                'service.activity:id,code,name,activity_type,starts_at',
                'serviceCategory:id,service_id,name,unit,value',
                'socialWorker:id,first_name,last_name,worker_code',
                'creator:id,name',
                'gateEntryAssignment:id,status,assigned_at,delivered_at,delivered_by',
                'activityAttendance:id,activity_id,status,checked_in_at,registration_method',
                'activityAttendance.activity:id,code,name,activity_type,starts_at',
            ])
            ->where(function (Builder $query) use ($person): void {
                $query->where('person_id', $person->id);

                if ($person->guardian_id) {
                    $query->orWhere('guardian_id', $person->guardian_id);
                }
            })
            ->latest('delivered_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function totals(?Person $person): array
    {
        if (! $person) {
            return [
                'direct_services_count' => 0,
                'direct_services_value' => 0,
                'family_services_count' => 0,
                'family_services_value' => 0,
                'activity_attendances_count' => 0,
                'manual_records_count' => 0,
                'manual_records_amount' => 0,
            ];
        }

        $directServices = ServiceDelivery::query()
            ->where('person_id', $person->id);

        $familyServices = ServiceDelivery::query()
            ->whereRaw('1 = 0');

        if ($person->guardian_id) {
            $familyServices = ServiceDelivery::query()
                ->where('guardian_id', $person->guardian_id)
                ->where(function (Builder $query) use ($person): void {
                    $query->whereNull('person_id')
                        ->orWhere('person_id', '!=', $person->id);
                });
        }

        return [
            'direct_services_count' => (clone $directServices)->count(),
            'direct_services_value' => (int) (clone $directServices)->sum('delivered_total_value'),
            'family_services_count' => (clone $familyServices)->count(),
            'family_services_value' => (int) (clone $familyServices)->sum('delivered_total_value'),
            'activity_attendances_count' => ActivityAttendance::query()
                ->where('person_id', $person->id)
                ->count(),
            'manual_records_count' => BeneficiaryCaseRecord::query()
                ->where('person_id', $person->id)
                ->count(),
            'manual_records_amount' => (int) BeneficiaryCaseRecord::query()
                ->where('person_id', $person->id)
                ->sum('amount'),
        ];
    }

    public function activityAttendances(?int $personId): Collection
    {
        if (! $personId) {
            return collect();
        }

        return ActivityAttendance::query()
            ->with([
                'activity:id,code,name,activity_type,starts_at,ends_at,location',
                'recorder:id,name',
                'serviceDeliveries.service:id,code,name,service_name_id,service_type,activity_id',
                'serviceDeliveries.service.serviceName:id,name',
                'serviceDeliveries.serviceCategory:id,service_id,name,unit,value',
                'serviceDeliveries.socialWorker:id,first_name,last_name,worker_code',
                'serviceDeliveries.creator:id,name',
            ])
            ->where('person_id', $personId)
            ->latest('checked_in_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function caseRecords(?int $personId): Collection
    {
        if (! $personId) {
            return collect();
        }

        return BeneficiaryCaseRecord::query()
            ->with(['creator:id,name', 'attachments:id,beneficiary_case_record_id,disk,path,original_name,mime_type,size'])
            ->where('person_id', $personId)
            ->latest('recorded_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function findEditingRecord(?int $personId, ?int $recordId): ?BeneficiaryCaseRecord
    {
        if (! $personId || ! $recordId) {
            return null;
        }

        return BeneficiaryCaseRecord::query()
            ->with(['attachments:id,beneficiary_case_record_id,disk,path,original_name,mime_type,size'])
            ->where('person_id', $personId)
            ->find($recordId);
    }

    public function findEditableRecordOrFail(?int $personId, int $recordId): BeneficiaryCaseRecord
    {
        return BeneficiaryCaseRecord::query()
            ->where('person_id', $personId)
            ->findOrFail($recordId);
    }

    public function findAttachmentForRecordOrFail(
        int $recordId,
        int $attachmentId,
    ): BeneficiaryCaseRecordAttachment {
        return BeneficiaryCaseRecordAttachment::query()
            ->where('beneficiary_case_record_id', $recordId)
            ->findOrFail($attachmentId);
    }

    public function attachmentCount(?int $recordId): int
    {
        if (! $recordId) {
            return 0;
        }

        return BeneficiaryCaseRecordAttachment::query()
            ->where('beneficiary_case_record_id', $recordId)
            ->count();
    }
}
