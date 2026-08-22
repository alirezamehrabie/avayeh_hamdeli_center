<?php

namespace Tests\Feature;

use App\Livewire\Admin\PrintClientCard;
use App\Models\User;
use App\Services\LabelPrinterService;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPrintClientCardTest extends TestCase
{
    public function test_print_client_card_mounts_with_zpl_default_layout_values(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->assertSet('paperWidthMm', 96)
            ->assertSet('labelWidthMm', 30)
            ->assertSet('labelHeightMm', 45)
            ->assertSet('gapMm', 3)
            ->assertSet('edgeMarginMm', 2)
            ->assertSet('topMarginMm', 3)
            ->assertSet('bottomMarginMm', 3)
            ->assertSet('columns', 2)
            ->assertSet('layoutMode', 'vertical');
    }

    public function test_direct_print_requires_preview_to_be_open_first(): void
    {
        $this->actingAs($this->adminUser());

        $printer = $this->mock(LabelPrinterService::class);
        $printer->shouldNotReceive('printBatch');

        Livewire::test(PrintClientCard::class)
            ->set('printList', [
                [
                    'id' => 1,
                    'full_name' => 'Test Person',
                    'national_id' => '1234567890',
                    'person_code' => '14001',
                ],
            ])
            ->call('printDirectly')
            ->assertSessionHas('error', 'ابتدا پیش‌نمایش چاپ را باز کنید.');
    }

    private function adminUser(): User
    {
        return User::query()->create([
            'name' => 'Admin User',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [],
        ]);
    }
}
