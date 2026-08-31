<?php

namespace Tests\Feature;

use App\Models\BeneficiaryCaseRecord;
use App\Models\BeneficiaryCaseRecordAttachment;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateCaseRecordAttachmentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrates_public_attachments_to_private_disk_and_deletes_public_file(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_ADMIN,
            'is_admin' => true,
            'permissions' => [User::PERMISSION_FULL_ACCESS],
        ]);
        $person = $this->person();
        $record = BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $admin->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_NOTE,
            'title' => 'Migration source',
            'recorded_at' => '2026-07-03',
        ]);

        $migratablePath = "beneficiary-case-records/{$person->id}/{$record->id}/migrate.pdf";
        Storage::disk('public')->put($migratablePath, 'private document content');

        $migratable = $this->createAttachment($record->id, $admin->id, 'public', $migratablePath, strlen('private document content'));

        $unavailablePath = "beneficiary-case-records/{$person->id}/{$record->id}/missing.pdf";
        $unavailable = $this->createAttachment($record->id, $admin->id, 'public', $unavailablePath, 100);

        $alreadyPrivatePath = "beneficiary-case-records/{$person->id}/{$record->id}/private.pdf";
        Storage::disk('local')->put($alreadyPrivatePath, 'already private');
        $alreadyPrivate = $this->createAttachment($record->id, $admin->id, 'local', $alreadyPrivatePath, strlen('already private'));

        $this->artisan('case-records:migrate-attachments')
            ->expectsOutputToContain('Migrated: 1')
            ->assertSuccessful();

        $this->assertSame('local', $migratable->fresh()->disk);
        Storage::disk('local')->assertExists($migratablePath);
        $this->assertSame('private document content', Storage::disk('local')->get($migratablePath));
        Storage::disk('public')->assertMissing($migratablePath);

        // فایل گمشده نباید دست بخورد؛ رکوردش روی public می‌ماند تا بررسی دستی شود.
        $this->assertSame('public', $unavailable->fresh()->disk);

        $this->assertSame('local', $alreadyPrivate->fresh()->disk);
        Storage::disk('local')->assertExists($alreadyPrivatePath);
    }

    public function test_dry_run_makes_no_changes(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create();
        $person = $this->person();
        $record = BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $admin->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_NOTE,
            'title' => 'Dry run',
            'recorded_at' => '2026-07-03',
        ]);

        $path = "beneficiary-case-records/{$person->id}/{$record->id}/keep.pdf";
        Storage::disk('public')->put($path, 'still public');

        $attachment = $this->createAttachment($record->id, $admin->id, 'public', $path, strlen('still public'));

        $this->artisan('case-records:migrate-attachments', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('public', $attachment->fresh()->disk);
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_cleanup_public_removes_orphan_files_after_successful_migration(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $admin = User::factory()->create();
        $person = $this->person();
        $record = BeneficiaryCaseRecord::query()->create([
            'person_id' => $person->id,
            'created_by' => $admin->id,
            'record_type' => BeneficiaryCaseRecord::TYPE_NOTE,
            'title' => 'Cleanup',
            'recorded_at' => '2026-07-03',
        ]);

        $referencedPath = "beneficiary-case-records/{$person->id}/{$record->id}/referenced.pdf";
        Storage::disk('public')->put($referencedPath, 'referenced');
        $referenced = $this->createAttachment($record->id, $admin->id, 'public', $referencedPath, strlen('referenced'));

        $orphanPath = "beneficiary-case-records/{$person->id}/{$record->id}/orphan.pdf";
        Storage::disk('public')->put($orphanPath, 'orphan');

        $this->artisan('case-records:migrate-attachments', ['--cleanup-public' => true])
            ->assertSuccessful();

        // فایل مرجع‌شده مهاجرت کرده و از public حذف شده است.
        $this->assertSame('local', $referenced->fresh()->disk);
        Storage::disk('local')->assertExists($referencedPath);
        $this->assertSame('referenced', Storage::disk('local')->get($referencedPath));

        // فایل یتیم (بدون رکورد) فقط با فلگ cleanup حذف می‌شود.
        Storage::disk('public')->assertMissing($orphanPath);
    }

    private function createAttachment(int $recordId, int $uploadedBy, string $disk, string $path, int $size): BeneficiaryCaseRecordAttachment
    {
        return BeneficiaryCaseRecordAttachment::query()->create([
            'beneficiary_case_record_id' => $recordId,
            'uploaded_by' => $uploadedBy,
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'application/pdf',
            'size' => $size,
        ]);
    }

    private function person(): Person
    {
        return Person::query()->create([
            'first_name' => 'Case',
            'last_name' => 'Beneficiary',
            'national_id' => (string) random_int(1000000000, 9999999999),
            'person_code' => (string) random_int(14000, 99999),
            'birth_year' => 1390,
            'birth_month' => 1,
            'birth_day' => 1,
        ]);
    }
}
