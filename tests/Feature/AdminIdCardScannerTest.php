<?php

namespace Tests\Feature;

use App\Livewire\Admin\IdCardScanner;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\User;
use App\Services\QrIdentityService;
use Livewire\Livewire;
use Tests\TestCase;

class AdminIdCardScannerTest extends TestCase
{
    public function test_person_qr_opens_beneficiary_modal_for_authorized_admin(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_EDIT],
        ]);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '1234567890',
            'person_code' => '14001',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $user->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($user);

        Livewire::test(IdCardScanner::class)
            ->call('resolveScannedQr', $token)
            ->assertSet('resolvedSubjectType', QrIdentity::SUBJECT_PERSON)
            ->assertSet('selectedPersonId', $person->id)
            ->assertSet('showPersonModal', true)
            ->assertSet('scanStatus', 'paused');
    }

    public function test_guardian_qr_opens_household_modal_for_full_access_user(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Moradi',
            'national_code' => '2234567890',
            'guardian_code' => 701,
        ]);

        $issued = app(QrIdentityService::class)->issueFor($guardian, $user->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($user);

        Livewire::test(IdCardScanner::class)
            ->call('resolveScannedQr', $token)
            ->assertSet('resolvedSubjectType', QrIdentity::SUBJECT_GUARDIAN)
            ->assertSet('selectedGuardianId', $guardian->id)
            ->assertSet('showHouseholdModal', true)
            ->assertSet('scanStatus', 'paused');
    }

    public function test_revoked_qr_sets_inline_error_state(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_EDIT],
        ]);

        $person = Person::query()->create([
            'first_name' => 'Reza',
            'last_name' => 'Karimi',
            'national_id' => '3234567890',
            'person_code' => '14002',
        ]);

        $issued = app(QrIdentityService::class)->issueFor($person, $user->id);
        $identity = $issued['identity'];
        $token = $issued['token'] ?? $identity->token_encrypted;

        app(QrIdentityService::class)->revoke($identity, $user->id, 'test revoke');

        $this->actingAs($user);

        Livewire::test(IdCardScanner::class)
            ->call('resolveScannedQr', $token)
            ->assertSet('scanStatus', 'scan_error')
            ->assertSet('showPersonModal', false)
            ->assertSet('showHouseholdModal', false);
    }

    public function test_non_full_access_user_cannot_open_guardian_result(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_EDIT],
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Hoda',
            'last_name' => 'Nazari',
            'national_code' => '4234567890',
            'guardian_code' => 702,
        ]);

        $issued = app(QrIdentityService::class)->issueFor($guardian, $user->id);
        $token = $issued['token'] ?? $issued['identity']->token_encrypted;

        $this->actingAs($user);

        Livewire::test(IdCardScanner::class)
            ->call('resolveScannedQr', $token)
            ->assertForbidden();
    }
}
