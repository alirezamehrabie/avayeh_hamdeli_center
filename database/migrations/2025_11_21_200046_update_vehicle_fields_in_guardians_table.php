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
            // حذف فیلد متنی قدیمی
            $table->dropColumn('vehicle_type');

            // اصلاح فیلد وضعیت (اطمینان از پیش‌فرض بودن 0)
            $table->boolean('has_vehicle')->default(0)->change();

            // افزودن رابطه به جدول جدید
            $table->foreignId('vehicle_type_id')
                ->nullable() // چون اگر ماشین نداشته باشد، این فیلد نال است
                ->after('has_vehicle')
                ->constrained('vehicle_types')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropForeign(['vehicle_type_id']);
            $table->dropColumn('vehicle_type_id');
            $table->string('vehicle_type')->nullable();
        });
    }

};
