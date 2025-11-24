<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            // اگر ستون bank_name هنوز حذف نشده، آن را حذف کن
            if (Schema::hasColumn('bank_infos', 'bank_name')) {
                $table->dropColumn('bank_name');
            }

            // ستون جدید را بعد از 'account_owner_relation_id' اضافه کن (نه account_holder_relation)
            // اگر شک دارید که ستون قبلی چیست، می‌توانید از 'has_own_account' استفاده کنید که مطمئن هستیم وجود دارد.
            $table->foreignId('bank_id')
                ->nullable()
                ->after('account_owner_relation_id') // <--- اصلاح شده
                ->constrained('banks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
            $table->string('bank_name')->nullable();
        });
    }
};
