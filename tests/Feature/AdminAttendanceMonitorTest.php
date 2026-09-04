<?php

namespace Tests\Feature;

use App\Livewire\Admin\AttendanceMonitor;
use App\Models\AttendanceSheet;
use App\Models\AttendanceSheetEntry;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAttendanceMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_today_sheet_with_worker_name(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(910);
        $sheet = $this->sheet($worker, $user, 'جلسه هفتگی شنبه');

        Livewire::test(AttendanceMonitor::class)
            ->assertSee('جلسه هفتگی شنبه')
            ->assertSee($worker->full_name);
    }

    public function test_user_without_social_worker_management_permission_is_forbidden(): void
    {
        [, $workerUser] = $this->socialWorkerUser(911);
        $this->actingAs($workerUser);

        Livewire::test(AttendanceMonitor::class)
            ->assertForbidden();
    }

    public function test_present_stat_counts_only_beneficiaries_without_check_out(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(912);
        $sheet = $this->sheet($worker, $user, 'کلاس تقویتی');
        $present = $this->personFor($worker, '2222222222', '18001');
        $gone = $this->personFor($worker, '2222222223', '18002');

        $this->entry($sheet, $present, $user);
        $this->entry($sheet, $gone, $user, out: now());

        Livewire::test(AttendanceMonitor::class)
            ->assertSee('حاضر همین حالا')
            ->assertSee('ورود ۲')
            ->assertSee('۱ حاضر');
    }

    public function test_present_tab_lists_only_currently_present_beneficiaries(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(913);
        $sheet = $this->sheet($worker, $user, 'اردوی مدرسه');
        $present = $this->personFor($worker, '3333333331', '18003', 'Sara', 'Present');
        $gone = $this->personFor($worker, '3333333332', '18004', 'Ali', 'Gone');

        $this->entry($sheet, $present, $user);
        $this->entry($sheet, $gone, $user, out: now());

        Livewire::test(AttendanceMonitor::class)
            ->call('setActiveTab', 'present')
            ->assertSee('Sara Present')
            ->assertDontSee('Ali Gone');
    }

    public function test_yesterday_sheet_is_hidden_in_today_range_and_visible_in_all(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(914);
        $sheet = $this->sheet($worker, $user, 'نشست قدیمی');
        $sheet->forceFill(['created_at' => now()->subDay()])->save();

        Livewire::test(AttendanceMonitor::class)
            ->assertDontSee('نشست قدیمی')
            ->call('setRange', 'all')
            ->assertSee('نشست قدیمی');
    }

    public function test_social_worker_filter_limits_visible_sheets(): void
    {
        $this->actingAs($this->manager());
        [$firstWorker, $firstUser] = $this->socialWorkerUser(915);
        [$secondWorker, $secondUser] = $this->socialWorkerUser(916);
        $this->sheet($firstWorker, $firstUser, 'شیت اول');
        $this->sheet($secondWorker, $secondUser, 'شیت دوم');

        Livewire::test(AttendanceMonitor::class)
            ->set('socialWorkerFilter', (string) $firstWorker->id)
            ->assertSee('شیت اول')
            ->assertDontSee('شیت دوم');
    }

    public function test_search_by_beneficiary_name_finds_the_sheet(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(917);
        $sheet = $this->sheet($worker, $user, 'کلاس هنر');
        $person = $this->personFor($worker, '4444444444', '18005', 'Maryam', 'Searchable');
        $this->entry($sheet, $person, $user);

        Livewire::test(AttendanceMonitor::class)
            ->set('search', 'Maryam')
            ->assertSee('کلاس هنر');
    }

    public function test_expanding_a_sheet_lists_its_entries(): void
    {
        $this->actingAs($this->manager());
        [$worker, $user] = $this->socialWorkerUser(918);
        $sheet = $this->sheet($worker, $user, 'جلسه کتابخوانی');
        $person = $this->personFor($worker, '5555555555', '18006', 'Reza', 'Reader');
        $this->entry($sheet, $person, $user);

        Livewire::test(AttendanceMonitor::class)
            ->assertDontSee('Reza Reader')
            ->call('toggleSheet', $sheet->id)
            ->assertSee('Reza Reader');
    }

    public function test_dashboard_serves_the_monitor_section_for_managers(): void
    {
        $this->actingAs($this->manager());

        $this->get(route('admin.dashboard', ['section' => 'social-worker-attendance-monitor']))
            ->assertOk()
            ->assertSee('پایش حضور و غیاب مددجویان');
    }

    public function test_dashboard_falls_back_to_overview_for_users_without_social_worker_access(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER],
        ]);
        $this->actingAs($user);

        $this->get(route('admin.dashboard', ['section' => 'social-worker-attendance-monitor']))
            ->assertOk()
            ->assertDontSee('پایش حضور و غیاب مددجویان');
    }

    private function manager(): User
    {
        return User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
    }

    /**
     * @return array{0: SocialWorker, 1: User}
     */
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

        return [$worker, $user];
    }

    private function sheet(SocialWorker $worker, User $user, string $name): AttendanceSheet
    {
        return AttendanceSheet::query()->create([
            'social_worker_id' => $worker->id,
            'name' => $name,
            'created_by' => $user->id,
        ]);
    }

    private function personFor(
        SocialWorker $worker,
        string $nationalId,
        string $personCode,
        string $firstName = 'Beneficiary',
        string $lastName = 'Person'
    ): Person {
        $guardian = Guardian::query()->firstOrCreate(
            ['social_worker_id' => $worker->id],
            [
                'guardian_code' => (int) substr($nationalId, 0, 7),
                'first_name' => 'Guardian',
                'last_name' => 'Of '.$worker->worker_code,
                'national_code' => '9'.substr($nationalId, 1),
            ]
        );

        return Person::query()->create([
            'guardian_id' => $guardian->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id' => $nationalId,
            'person_code' => $personCode,
        ]);
    }

    private function entry(
        AttendanceSheet $sheet,
        Person $person,
        User $recorder,
        ?\Illuminate\Support\Carbon $in = null,
        ?\Illuminate\Support\Carbon $out = null
    ): AttendanceSheetEntry {
        return AttendanceSheetEntry::query()->create([
            'attendance_sheet_id' => $sheet->id,
            'person_id' => $person->id,
            'person_name' => $person->full_name ?: trim($person->first_name.' '.$person->last_name),
            'person_code' => $person->person_code,
            'national_id' => $person->national_id,
            'check_in_method' => 'qr',
            'checked_in_at' => $in ?? now(),
            'checked_in_by' => $recorder->id,
            'check_out_method' => $out ? 'manual' : null,
            'checked_out_at' => $out,
            'checked_out_by' => $out ? $recorder->id : null,
        ]);
    }
}
