<?php

namespace Tests\Feature;

use App\Livewire\Admin\PrintClientCard;
use App\Models\Guardian;
use App\Models\Person;
use App\Models\SocialWorker;
use App\Models\User;
use App\Services\LabelPrinterService;
use Illuminate\Http\Testing\File;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPrintClientCardTest extends TestCase
{
    public function test_print_client_card_mounts_with_zpl_default_layout_values(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->assertSet('paperWidthMm', 96)
            ->assertSet('labelWidthMm', 45)
            ->assertSet('labelHeightMm', 30)
            ->assertSet('gapMm', 3)
            ->assertSet('qrTextGapMm', -8)
            ->assertSet('qrTextRotationDeg', 0)
            ->assertSet('edgeMarginMm', 1.5)
            ->assertSet('topMarginMm', 3)
            ->assertSet('bottomMarginMm', 3)
            ->assertSet('columns', 2)
            ->assertSet('layoutMode', 'horizontal');
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
            ->assertSet('showPreview', false)
            ->assertSet('printingDirectly', false);
    }

    public function test_layout_settings_export_downloads_json_file(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->set('textFontSize', 40)
            ->call('exportLayoutSettings')
            ->assertFileDownloaded();
    }

    public function test_layout_settings_can_be_exported_and_reimported(): void
    {
        $this->actingAs($this->adminUser());

        $exporter = Livewire::test(PrintClientCard::class)
            ->set('labelWidthMm', 52.5)
            ->set('labelHeightMm', 33)
            ->set('paperWidthMm', 100)
            ->set('gapMm', 4.5)
            ->set('qrTextGapMm', 2)
            ->set('qrTextRotationDeg', 90)
            ->set('edgeMarginMm', 2)
            ->set('topMarginMm', 5)
            ->set('bottomMarginMm', 6)
            ->set('dpi', 300)
            ->set('qrSizeDots', 220)
            ->set('qrMagnification', 6)
            ->set('qrErrorCorrection', 'H')
            ->set('textFontSize', 40)
            ->set('textBottomOffsetDots', 15)
            ->set('rotate180', true)
            ->set('layoutMode', 'vertical')
            ->call('exportLayoutSettings');

        $json = base64_decode(data_get($exporter->effects, 'download.content'));

        $this->assertIsString($json);

        Livewire::test(PrintClientCard::class)
            ->call('resetLayoutDefaults')
            ->set('layoutImportFile', File::fake()->createWithContent('label-layout-settings.json', $json))
            ->call('importLayoutSettings')
            ->assertSet('layoutImportFile', null)
            ->assertSet('labelWidthMm', 52.5)
            ->assertSet('labelHeightMm', 33.0)
            ->assertSet('paperWidthMm', 100.0)
            ->assertSet('gapMm', 4.5)
            ->assertSet('qrTextGapMm', 2.0)
            ->assertSet('qrTextRotationDeg', 90)
            ->assertSet('edgeMarginMm', 2.0)
            ->assertSet('topMarginMm', 5.0)
            ->assertSet('bottomMarginMm', 6.0)
            ->assertSet('dpi', 300)
            ->assertSet('qrSizeDots', 220)
            ->assertSet('qrMagnification', 6)
            ->assertSet('qrErrorCorrection', 'H')
            ->assertSet('textFontSize', 40)
            ->assertSet('textBottomOffsetDots', 15)
            ->assertSet('rotate180', true)
            ->assertSet('layoutMode', 'vertical');
    }

    public function test_import_clamps_out_of_range_values_and_skips_invalid_ones(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->set('layoutImportFile', File::fake()->createWithContent('settings.json', json_encode([
                'settings' => [
                    'label_width_mm' => 999,
                    'qr_size_dots' => -50,
                    'dpi' => 100,
                    'layout_mode' => 'diagonal',
                    'rotate_180' => 'true',
                    'textFontSize' => 48,
                ],
            ])))
            ->call('importLayoutSettings')
            ->assertSet('labelWidthMm', 200.0)
            ->assertSet('qrSizeDots', 50)
            ->assertSet('dpi', 203)
            ->assertSet('layoutMode', 'horizontal')
            ->assertSet('rotate180', true)
            ->assertSet('textFontSize', 48);
    }

    public function test_import_rejects_files_without_json_extension(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->set('layoutImportFile', File::fake()->createWithContent('settings.txt', '{}'))
            ->call('importLayoutSettings')
            ->assertSet('layoutImportFile', null)
            ->assertSet('textFontSize', 24)
            ->assertSet('labelWidthMm', 45);
    }

    public function test_import_rejects_invalid_json_content(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->set('layoutImportFile', File::fake()->createWithContent('broken.json', '{not-valid-json'))
            ->call('importLayoutSettings')
            ->assertSet('layoutImportFile', null)
            ->assertSet('textFontSize', 24)
            ->assertSet('labelWidthMm', 45);
    }

    public function test_import_requires_a_selected_file_first(): void
    {
        $this->actingAs($this->adminUser());

        Livewire::test(PrintClientCard::class)
            ->call('importLayoutSettings')
            ->assertSet('labelWidthMm', 45)
            ->assertSet('textFontSize', 24);
    }

    public function test_adding_social_worker_clients_replaces_the_existing_print_list(): void
    {
        $this->actingAs($this->adminUser());

        $worker = SocialWorker::query()->create([
            'worker_code' => random_int(1000000, 9999999),
            'first_name' => 'Print',
            'last_name' => 'Worker',
            'is_active' => true,
        ]);

        $guardian = Guardian::query()->create([
            'social_worker_id' => $worker->id,
            'guardian_code' => random_int(1000000, 9999999),
            'first_name' => 'Print',
            'last_name' => 'Guardian',
        ]);

        $client = Person::query()->create([
            'guardian_id' => $guardian->id,
            'person_code' => (string) random_int(1000000, 9999999),
            'national_id' => (string) random_int(1000000000, 9999999999),
            'first_name' => 'Print',
            'last_name' => 'Client',
        ]);

        $component = Livewire::test(PrintClientCard::class)
            ->set('printList', [
                [
                    'id' => 987654,
                    'full_name' => 'Previous Client',
                    'national_id' => '0000000000',
                    'person_code' => '14001',
                ],
            ])
            ->set('selectedSocialWorkerId', $worker->id)
            ->call('addSocialWorkerClientsToPrintList');

        $this->assertSame(
            [$client->id],
            collect($component->get('printList'))->pluck('id')->all()
        );
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
