<?php

namespace App\Console\Commands;

use App\Models\BeneficiaryCaseRecordAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * انتقال پیوست‌های قدیمی پرونده مددجو از دیسک public به دیسک خصوصی (local).
 *
 * ترتیب عمداً محتاطانه است تا در صورت بروز خطا هیچ داده‌ای از دست نرود:
 *   کپی public → local، تأیید یکسان بودن اندازه، به‌روزرسانی رکورد دیتابیس،
 * و در نهایت حذف فایل عمومی. اگر هر مرحله‌ای شکست بخورد، رکورد دست‌نخورده
 * می‌ماند و اجرای مجدد دستور (idempotent) ادامه‌ی کار را انجام می‌دهد.
 */
class MigrateCaseRecordAttachments extends Command
{
    protected $signature = 'case-records:migrate-attachments
        {--dry-run : فقط گزارش بده، هیچ تغییری اعمال نکن}
        {--cleanup-public : فایل‌های جامانده در دیسک public را هم پاک کن}';

    protected $description = 'Move beneficiary case-record attachments from the public disk to the private local disk.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');

        $migrated = 0;
        $verified = 0;
        $missing = 0;
        $failed = 0;

        BeneficiaryCaseRecordAttachment::query()
            ->where('disk', 'public')
            ->orderBy('id')
            ->lazyById(200)
            ->each(function (BeneficiaryCaseRecordAttachment $attachment) use (&$migrated, &$verified, &$missing, &$failed, $publicDisk, $localDisk, $dryRun): void {
                $publicExists = $publicDisk->exists($attachment->path);
                $localExists = $localDisk->exists($attachment->path);

                if (! $publicExists && ! $localExists) {
                    $missing++;
                    $this->warn("SKIP (file not found on any disk) [{$attachment->id}] {$attachment->path}");

                    return;
                }

                if ($publicExists && $localExists && ! $this->sizesMatch($attachment, $localDisk)) {
                    $failed++;
                    $this->warn("SKIP (size mismatch on both disks, needs manual review) [{$attachment->id}] {$attachment->path}");

                    return;
                }

                $copied = false;

                if ($publicExists && ! $localExists) {
                    if (! $this->copyAndVerify($attachment, $publicDisk, $localDisk, $dryRun)) {
                        $failed++;

                        return;
                    }
                    $copied = true;
                }

                // فایل روی local موجود و سالم است؛ حالا رکورد و سپس فایل عمومی حذف می‌شود.
                if (! $dryRun) {
                    $attachment->forceFill(['disk' => 'local'])->save();

                    if ($publicExists && $publicDisk->delete($attachment->path) === false) {
                        $this->warn("DB updated but public file could not be deleted (re-run with --cleanup-public) [{$attachment->id}] {$attachment->path}");
                    }
                }

                if ($copied) {
                    $migrated++;
                    $this->info(($dryRun ? 'WOULD COPY' : 'COPIED')." [{$attachment->id}] {$attachment->path}");
                } else {
                    $verified++;
                    $this->info(($dryRun ? 'WOULD FINALIZE' : 'FINALIZED')." [{$attachment->id}] {$attachment->path}");
                }
            });

        if ($dryRun) {
            $this->line('Dry run — no changes were applied.');
        }

        $this->info("Migrated: {$migrated}, finalized: {$verified}, missing: {$missing}, failed: {$failed}");

        if ($failed > 0) {
            $this->warn('Some rows failed; those rows were left untouched on the public disk. Re-run this command after fixing the issue.');

            return self::FAILURE;
        }

        if ((bool) $this->option('cleanup-public')) {
            $this->cleanupLeftoversOnPublicDisk($publicDisk, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * کپی با استریم + تأیید اندازه؛ در صورت هر ناهماهنگی، نسخه‌ی local حذف
     * و رکورد به حالت قبل برمی‌گردد.
     */
    private function copyAndVerify(
        BeneficiaryCaseRecordAttachment $attachment,
        $publicDisk,
        $localDisk,
        bool $dryRun,
    ): bool {
        if ($dryRun) {
            return true;
        }

        $stream = $publicDisk->readStream($attachment->path);

        if ($stream === null || ! $localDisk->writeStream($attachment->path, $stream)) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            $localDisk->delete($attachment->path);
            $this->warn("FAILED to copy [{$attachment->id}] {$attachment->path}");

            return false;
        }

        fclose($stream);

        if (! $localDisk->exists($attachment->path) || ! $this->sizesMatch($attachment, $localDisk)) {
            $localDisk->delete($attachment->path);
            $this->warn("FAILED verification after copy [{$attachment->id}] {$attachment->path}");

            return false;
        }

        return true;
    }

    private function sizesMatch(BeneficiaryCaseRecordAttachment $attachment, $localDisk): bool
    {
        if ((int) $attachment->size <= 0) {
            return true;
        }

        return (int) $localDisk->size($attachment->path) === (int) $attachment->size;
    }

    /**
     * فقط فایل‌هایی حذف می‌شوند که هیچ رکوردی در دیتابیس به آن‌ها اشاره نمی‌کند
     * و مسیرشان حتماً زیر پوشه‌ی beneficiary-case-records است.
     */
    private function cleanupLeftoversOnPublicDisk($publicDisk, bool $dryRun): void
    {
        $referenced = BeneficiaryCaseRecordAttachment::query()
            ->where('disk', 'public')
            ->pluck('path')
            ->all();

        $deleted = 0;

        foreach ($publicDisk->allFiles('beneficiary-case-records') as $path) {
            if (in_array($path, $referenced, true)) {
                continue;
            }

            if ($dryRun) {
                $this->line("WOULD DELETE orphan public file: {$path}");
                $deleted++;

                continue;
            }

            if ($publicDisk->delete($path)) {
                $deleted++;
            } else {
                $this->warn("Could not delete orphan public file: {$path}");
            }
        }

        $this->info("Orphan public files removed: {$deleted}");
    }
}
