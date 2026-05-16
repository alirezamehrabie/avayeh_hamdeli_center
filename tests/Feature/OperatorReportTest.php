<?php

namespace Tests\Feature;

use App\Livewire\Admin\OperatorReport;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperatorReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_reports_for_all_operators(): void
    {
        $manager = User::factory()->create([
            'name' => 'manager-user',
            'first_name' => 'مدیر',
            'last_name' => 'سیستم',
            'email' => 'manager@example.com',
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $operator = User::factory()->create([
            'name' => 'operator-user',
            'first_name' => 'اپراتور',
            'last_name' => 'اول',
            'email' => 'operator@example.com',
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER, User::PERMISSION_PEOPLE_EDIT, User::PERMISSION_PEOPLE_DELETE],
        ]);

        $this->createLoggedPerson($operator, '1234567890', 'آرمان', 'کریمی');
        $this->createLoggedPerson($manager, '1234567891', 'ندا', 'احمدی');

        Livewire::actingAs($manager)
            ->test(OperatorReport::class)
            ->set('timeframe', 'month')
            ->set('reference', '2026-06')
            ->assertSee('مدیر سیستم')
            ->assertSee('اپراتور اول');
    }

    public function test_admin_can_only_view_their_own_report(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin-user',
            'first_name' => 'ادمین',
            'last_name' => 'سامانه',
            'email' => 'admin@example.com',
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $operator = User::factory()->create([
            'name' => 'operator-user',
            'first_name' => 'اپراتور',
            'last_name' => 'اول',
            'email' => 'operator@example.com',
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER, User::PERMISSION_PEOPLE_EDIT, User::PERMISSION_PEOPLE_DELETE],
        ]);

        $this->createLoggedPerson($admin, '1234567890', 'ندا', 'احمدی');
        $this->createLoggedPerson($operator, '1234567891', 'آرمان', 'کریمی');

        Livewire::actingAs($operator)
            ->test(OperatorReport::class)
            ->set('timeframe', 'month')
            ->set('reference', '2026-06')
            ->assertSee('اپراتور اول')
            ->assertDontSee('ادمین سامانه');
    }

    public function test_regular_operator_can_only_view_their_own_report(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin-user',
            'first_name' => 'ادمین',
            'last_name' => 'سامانه',
            'email' => 'admin@example.com',
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $operator = User::factory()->create([
            'name' => 'operator-user',
            'first_name' => 'اپراتور',
            'last_name' => 'اول',
            'email' => 'operator@example.com',
            'access_level' => User::ACCESS_LEVEL_REGULAR,
            'is_admin' => false,
            'permissions' => [User::PERMISSION_PEOPLE_REGISTER, User::PERMISSION_PEOPLE_EDIT, User::PERMISSION_PEOPLE_DELETE],
        ]);

        $this->createLoggedPerson($admin, '1234567890', 'ندا', 'احمدی');
        $this->createLoggedPerson($operator, '1234567891', 'آرمان', 'کریمی');

        Livewire::actingAs($operator)
            ->test(OperatorReport::class)
            ->set('timeframe', 'month')
            ->set('reference', '2026-06')
            ->assertSee('اپراتور اول')
            ->assertDontSee('ادمین سامانه');
    }

    private function createLoggedPerson(User $actor, string $nationalId, string $firstName, string $lastName): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');
        $this->actingAs($actor);

        $person = Person::create([
            'national_id' => $nationalId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'male',
            'role' => 'child',
        ]);

        Carbon::setTestNow('2026-06-15 11:00:00');
        $person->update([
            'phone_number' => '09120000000',
        ]);

        Carbon::setTestNow('2026-06-15 12:00:00');
        $person->delete();

        Carbon::setTestNow();
    }
}
