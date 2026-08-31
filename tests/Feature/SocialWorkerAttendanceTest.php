<?php

namespace Tests\Feature;

use App\Livewire\SocialWorkers\Attendance;
use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\AttendanceSheetService;
use App\Services\QrIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SocialWorkerAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_creates_a_sheet_and_lands_on_check_in_mode(): void
    {
        [$user] = $this->socialWorkerUser(701);
        $this->actingAs($user);

        Livewire::test(Attendance::class)
            ->set('newSheetName', 'کلاس قرآن پنجشنبه')
            ->call('createSheet')
            ->assertHasNoErrors()
            ->assertSet('mode', AttendanceSheet::MODE_IN)
            ->assertSet('sheetId', fn ($sheetId): bool => $sheetId !== null);

        $this->assertDatabaseHas('attendance_sheets', [
            'name' => 'کلاس قرآن پنجشنبه',
            'created_by' => $user->id,
        ]);
    }

    public function test_sheet_name_must_be_unique_per_social_worker(): void
    {
        [$firstUser, $firstWorker] = $this->socialWorkerUser(702);
        [$secondUser] = $this->socialWorkerUser(703);

        AttendanceSheet::query()->create([
            'social_worker_id' => $firstWorker->id,
            'name' => 'اردوی زیارتی',
            'created_by' => $firstUser->id,
        ]);

        $this->actingAs($firstUser);

        Livewire::test(Attendance::class)
            ->set('newSheetName', 'اردوی زیارتی')
            ->call('createSheet')
            ->assertHasErrors(['newSheetName' => 'unique']);

        // The same name is still free for a different social worker.
        $this->actingAs($secondUser);

        Livewire::test(Attendance::class)
            ->set('newSheetName', 'اردوی زیارتی')
            ->call('createSheet')
            ->assertHasNoErrors();

        $this->assertSame(2, AttendanceSheet::query()->where('name', 'اردوی زیارتی')->count());
    }

    public function test_scanning_in_check_in_mode_records_an_entry_and_repeat_scan_is_reported_as_duplicate(): void
    {
        [$user, $worker] = $this->socialWorkerUser(704);
        $sheet = $this->sheet($worker, $user, 'جلسه آموزشی');
        $person = $this->personFor($worker, '1111111111', '17001');
        $token = $this->qrTokenFor($person, $user);

        $service = app(AttendanceSheetService::class);

        $first = $service->checkInByQr($sheet, $token, $user);
        $second = $service->checkInByQr($sheet, $token, $user);

        $this->assertTrue($first->ok);
        $this->assertSame('checked_in', $first->code);
        $this->assertTrue($second->ok);
        $this->assertSame('duplicate', $second->code);

        $entries = AttendanceSheetEntry::query()->where('attendance_sheet_id', $sheet->id)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('qr', $entries->first()->check_in_method);
        $this->assertSame($user->id, $entries->first()->checked_in_by);
        $this->assertNotNull($entries->first()->checked_in_at);
        $this->assertSame($person->national_id, $entries->first()->national_id);
    }

    public function test_check_out_mode_stamps_the_existing_entry_without_touching_the_check_in_time(): void
    {
        [$user, $worker] = $this->socialWorkerUser(705);
        $sheet = $this->sheet($worker, $user, 'کارگاه مهارت');
        $person = $this->personFor($worker, '2222222222', '17002');
        $token = $this->qrTokenFor($person, $user);

        $service = app(AttendanceSheetService::class);
        $service->checkInByQr($sheet, $token, $user);

        $checkedInAt = AttendanceSheetEntry::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->where('person_id', $person->id)
            ->value('checked_in_at');

        $this->travel(7)->minutes();

        $result = $service->checkOutByQr($sheet, $token, $user);

        $this->assertTrue($result->ok);
        $this->assertSame('checked_out', $result->code);

        $entry = AttendanceSheetEntry::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->where('person_id', $person->id)
            ->firstOrFail();

        $this->assertSame((string) $checkedInAt, (string) $entry->checked_in_at);
        $this->assertNotNull($entry->checked_out_at);
        $this->assertSame('qr', $entry->check_out_method);
        $this->assertSame($user->id, $entry->checked_out_by);
    }

    public function test_check_out_is_rejected_when_no_check_in_exists_and_reported_once_already_checked_out(): void
    {
        [$user, $worker] = $this->socialWorkerUser(706);
        $sheet = $this->sheet($worker, $user, 'نشست هفتگی');
        $person = $this->personFor($worker, '3333333333', '17003');
        $token = $this->qrTokenFor($person, $user);

        $service = app(AttendanceSheetService::class);

        $withoutCheckIn = $service->checkOutByQr($sheet, $token, $user);

        $this->assertFalse($withoutCheckIn->ok);
        $this->assertSame('not_checked_in', $withoutCheckIn->code);

        $service->checkInByQr($sheet, $token, $user);
        $service->checkOutByQr($sheet, $token, $user);

        $firstCheckedOutAt = AttendanceSheetEntry::query()
            ->where('attendance_sheet_id', $sheet->id)
            ->where('person_id', $person->id)
            ->value('checked_out_at');

        $this->travel(3)->minutes();
        $repeat = $service->checkOutByQr($sheet, $token, $user);

        $this->assertTrue($repeat->ok);
        $this->assertSame('already_checked_out', $repeat->code);
        $this->assertSame(
            (string) $firstCheckedOutAt,
            (string) AttendanceSheetEntry::query()
                ->where('attendance_sheet_id', $sheet->id)
                ->where('person_id', $person->id)
                ->value('checked_out_at')
        );
    }

    public function test_a_social_worker_cannot_scan_a_beneficiary_of_another_worker(): void
    {
        [$user, $worker] = $this->socialWorkerUser(707);
        [, $otherWorker] = $this->socialWorkerUser(708);
        $sheet = $this->sheet($worker, $user, 'گروه ورزشی');
        $foreignPerson = $this->personFor($otherWorker, '4444444444', '17004');
        $token = $this->qrTokenFor($foreignPerson, $user);

        $result = app(AttendanceSheetService::class)->checkInByQr($sheet, $token, $user);

        $this->assertFalse($result->ok);
        $this->assertSame('not_own_beneficiary', $result->code);
        $this->assertDatabaseCount('attendance_sheet_entries', 0);
    }

    public function test_guardian_qr_is_rejected_because_attendance_tracks_beneficiaries(): void
    {
        [$user, $worker] = $this->socialWorkerUser(709);
        $sheet = $this->sheet($worker, $user, 'جلسه خانواده');
        $guardian = $this->guardianFor($worker, '5555555555');
        $token = $this->qrTokenFor($guardian, $user);

        $result = app(AttendanceSheetService::class)->checkInByQr($sheet, $token, $user);

        $this->assertFalse($result->ok);
        $this->assertSame('not_beneficiary', $result->code);
    }

    public function test_component_scan_switches_behavior_with_mode_and_lists_only_own_sheets(): void
    {
        [$user, $worker] = $this->socialWorkerUser(710);
        [$otherUser, $otherWorker] = $this->socialWorkerUser(711);
        $sheet = $this->sheet($worker, $user, 'اردوی تابستان');
        $this->sheet($otherWorker, $otherUser, 'اردوی دیگران');
        $person = $this->personFor($worker, '6666666666', '17005');
        $token = $this->qrTokenFor($person, $user);

        $this->actingAs($user);

        $component = Livewire::test(Attendance::class)
            ->assertViewHas('sheets', fn ($sheets): bool => $sheets->count() === 1
                && $sheets->first()->name === 'اردوی تابستان')
            ->call('openSheet', $sheet->id)
            ->assertSet('mode', AttendanceSheet::MODE_IN);

        $component->call('resolveScannedQr', $token);

        $this->assertSame('checked_in', $component->get('lastScanResult')['code']);

        $component->call('setMode', AttendanceSheet::MODE_OUT)
            ->assertSet('mode', AttendanceSheet::MODE_OUT)
            ->call('resolveScannedQr', $token);

        $this->assertSame('checked_out', $component->get('lastScanResult')['code']);
        $this->assertNotNull(
            AttendanceSheetEntry::query()
                ->where('attendance_sheet_id', $sheet->id)
                ->where('person_id', $person->id)
                ->value('checked_out_at')
        );
    }

    public function test_opening_another_workers_sheet_is_forbidden(): void
    {
        [$user] = $this->socialWorkerUser(712);
        [$otherUser, $otherWorker] = $this->socialWorkerUser(713);
        $foreignSheet = $this->sheet($otherWorker, $otherUser, 'حضور و غیاب دیگری');

        $this->actingAs($user);

        Livewire::test(Attendance::class)
            ->call('openSheet', $foreignSheet->id)
            ->assertStatus(404);
    }

    public function test_manual_check_out_candidates_exclude_people_who_already_left(): void
    {
        [$user, $worker] = $this->socialWorkerUser(714);
        $sheet = $this->sheet($worker, $user, 'کلاس هنر');
        $stillInside = $this->personFor($worker, '7777777777', '17006', 'Inside', 'Person');
        $alreadyOut = $this->personFor($worker, '8888888888', '17007', 'Outside', 'Person');

        $service = app(AttendanceSheetService::class);
        $service->checkInPerson($sheet, $stillInside, $user, 'manual');
        $service->checkInPerson($sheet, $alreadyOut, $user, 'manual');
        $service->checkOutPerson($sheet, $alreadyOut, $user, 'manual');

        $this->actingAs($user);

        Livewire::test(Attendance::class)
            ->call('openSheet', $sheet->id)
            ->call('setMode', AttendanceSheet::MODE_OUT)
            ->set('manualSearch', 'Person')
            ->assertViewHas('manualCandidates', fn ($candidates): bool => $candidates->pluck('id')->all() === [$stillInside->id]);
    }

    public function test_sheet_list_shows_check_in_and_check_out_totals(): void
    {
        [$user, $worker] = $this->socialWorkerUser(716);
        $sheet = $this->sheet($worker, $user, 'کلاس رباتیک');
        $stayed = $this->personFor($worker, '1212121212', '17008');
        $left = $this->personFor($worker, '1313131313', '17009');

        $service = app(AttendanceSheetService::class);
        $service->checkInPerson($sheet, $stayed, $user, 'manual');
        $service->checkInPerson($sheet, $left, $user, 'manual');
        $service->checkOutPerson($sheet, $left, $user, 'manual');

        $this->actingAs($user);

        Livewire::test(Attendance::class)
            ->assertViewHas('sheets', function ($sheets) use ($sheet): bool {
                $listed = $sheets->firstWhere('id', $sheet->id);

                return $listed !== null
                    && (int) $listed->checked_in_count === 2
                    && (int) $listed->checked_out_count === 1;
            })
            ->assertSee('کلاس رباتیک');
    }

    public function test_attendance_page_is_reachable_by_a_social_worker_and_closed_to_others(): void
    {
        [$user] = $this->socialWorkerUser(715);

        $this->actingAs($user)
            ->get(route('social-worker.attendance'))
            ->assertOk()
            ->assertSee('حضور و غیاب');

        $outsider = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_CHILD_SUPPORTER,
            'is_admin' => false,
            'permissions' => [],
        ]);

        // Browser 403s are redirected to the user's own panel, so assert the
        // JSON response where the app keeps the raw 403.
        $this->actingAs($outsider)
            ->getJson(route('social-worker.attendance'))
            ->assertForbidden();
    }

    private function socialWorkerUser(int $workerCode): array
    {
        $worker = SocialWorker::query()->create([
            'worker_code' => $workerCode,
            'first_name' => 'Social',
            'last_name' => 'Worker '.$workerCode,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_SOCIAL_WORKER,
            'is_admin' => false,
            'permissions' => [],
            'social_worker_id' => $worker->id,
        ]);

        return [$user, $worker];
    }

    private function sheet(SocialWorker $worker, User $user, string $name): AttendanceSheet
    {
        return AttendanceSheet::query()->create([
            'social_worker_id' => $worker->id,
            'name' => $name,
            'created_by' => $user->id,
        ]);
    }

    private function guardianFor(SocialWorker $worker, string $nationalCode): Guardian
    {
        return Guardian::query()->create([
            'social_worker_id' => $worker->id,
            'guardian_code' => (int) substr($nationalCode, 0, 7),
            'first_name' => 'Guardian',
            'last_name' => 'Of '.$worker->worker_code,
            'national_code' => $nationalCode,
        ]);
    }

    private function personFor(
        SocialWorker $worker,
        string $nationalId,
        string $personCode,
        string $firstName = 'Beneficiary',
        string $lastName = 'Person'
    ): Person {
        $guardian = $this->guardianFor($worker, '9'.substr($nationalId, 1));

        return Person::query()->create([
            'guardian_id' => $guardian->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id' => $nationalId,
            'person_code' => $personCode,
        ]);
    }

    private function qrTokenFor(Person|Guardian $subject, User $user): string
    {
        // PersonObserver already issues an identity on create, so issueFor() returns
        // the existing one with a null token - fall back to the stored value.
        $issued = app(QrIdentityService::class)->issueFor($subject, $user->id);

        return $issued['token'] ?? $issued['identity']->token_encrypted;
    }
}
