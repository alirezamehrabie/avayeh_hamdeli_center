<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOccupationIdToGuardiansTable extends Migration
{
    public function up()
    {
        Schema::table('guardians', function (Blueprint $table) {
            // ۱. اگر قبلاً فیلد متنی occupation دارید، حذفش کنید:
            if (Schema::hasColumn('guardians', 'occupation')) {
                $table->dropColumn('occupation');
            }
            // ۲. اضافه کردن occupation_id با Foreign Key
            $table->foreignId('occupation_id')
                ->nullable()             // یا required بسته به نیاز
                ->constrained('occupations')
                ->cascadeOnUpdate()
                ->nullOnDelete();        // یا cascadeOnDelete()
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropConstrainedForeignId('occupation_id');
            // در صورت نیاز، می‌توانید ستون متنی occupation را دوباره تعریف کنید
            $table->string('occupation')->nullable();
        });
    }
}
