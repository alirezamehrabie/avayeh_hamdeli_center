<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('guardians', function (Blueprint $table) {
            // حذف ستون قدیمی رشته‌ای
            $table->dropColumn('job_type');

            // افزودن ستون کلید خارجی جدید
            $table->foreignId('job_type_id')
                ->nullable()
                ->after('occupation_id') // اختیاری: برای مرتب بودن ستون‌ها
                ->constrained('job_types')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropForeign(['job_type_id']);
            $table->dropColumn('job_type_id');
            $table->string('job_type')->nullable();
        });
    }

};
