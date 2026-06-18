<?php

namespace Tests\Feature;

use App\Livewire\Shared\QrIdentityModal;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class QrIdentityModalTest extends TestCase
{
    public function test_person_qr_modal_loads_prepared_qr_state(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_EDIT],
        ]);

        $person = Person::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'full_name' => 'Ali Ahmadi',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(9000000, 9999999),
        ]);

        $this->actingAs($user);

        Livewire::test(QrIdentityModal::class)
            ->call('open', QrIdentity::SUBJECT_PERSON, $person->id)
            ->assertSet('showQrModal', true)
            ->assertSet('qrSubjectType', QrIdentity::SUBJECT_PERSON)
            ->assertSet('qrSubjectId', $person->id)
            ->assertSee('PQR-')
            ->assertSeeHtml('<svg');
    }

    public function test_guardian_qr_modal_loads_prepared_qr_state(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Moradi',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_code' => random_int(9000000, 9999999),
        ]);

        $this->actingAs($user);

        Livewire::test(QrIdentityModal::class)
            ->call('open', QrIdentity::SUBJECT_GUARDIAN, $guardian->id)
            ->assertSet('showQrModal', true)
            ->assertSet('qrSubjectType', QrIdentity::SUBJECT_GUARDIAN)
            ->assertSet('qrSubjectId', $guardian->id)
            ->assertSee('GQR-')
            ->assertSeeHtml('<svg');
    }

    public function test_reissue_replaces_prepared_qr_state(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $person = Person::query()->create([
            'first_name' => 'Mina',
            'last_name' => 'Rahimi',
            'full_name' => 'Mina Rahimi',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(9000000, 9999999),
        ]);

        $this->actingAs($user);

        $component = Livewire::test(QrIdentityModal::class)
            ->call('open', QrIdentity::SUBJECT_PERSON, $person->id);

        $oldPublicCode = $component->get('publicCode');

        $component
            ->call('requestQrLifecycleAction', 'reissue')
            ->set('qrLifecycleReason', 'Valid reason for reissue')
            ->call('confirmQrLifecycleAction')
            ->assertSet('showQrModal', true)
            ->assertSet('confirmingQrLifecycleAction', false);

        $this->assertNotSame($oldPublicCode, $component->get('publicCode'));
        $this->assertNotNull($component->get('qrMarkup'));
    }

    public function test_revoke_clears_prepared_qr_state(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Hoda',
            'last_name' => 'Nazari',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_code' => random_int(9000000, 9999999),
        ]);

        $this->actingAs($user);

        Livewire::test(QrIdentityModal::class)
            ->call('open', QrIdentity::SUBJECT_GUARDIAN, $guardian->id)
            ->call('requestQrLifecycleAction', 'revoke')
            ->set('qrLifecycleReason', 'Valid reason for revoke')
            ->call('confirmQrLifecycleAction')
            ->assertSet('publicCode', null)
            ->assertSet('scanUrl', null)
            ->assertSet('qrMarkup', null);
    }

    public function test_non_full_access_user_cannot_open_guardian_qr_modal(): void
    {
        $user = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_PEOPLE_EDIT],
        ]);

        $guardian = Guardian::query()->create([
            'first_name' => 'Nima',
            'last_name' => 'Karimi',
            'national_code' => (string) random_int(1000000000, 9999999999),
            'guardian_code' => random_int(9000000, 9999999),
        ]);

        $this->actingAs($user);

        Livewire::test(QrIdentityModal::class)
            ->call('open', QrIdentity::SUBJECT_GUARDIAN, $guardian->id)
            ->assertForbidden();
    }
}
