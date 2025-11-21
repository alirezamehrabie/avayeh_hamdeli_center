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
            // 1. حذف ستون متنی قدیمی
            $table->dropColumn('other_insurance');

            // 2. تغییر نوع ستون وضعیت بیمه به بولین (اگر وجود دارد اول حذف میکنیم تا دوباره بسازیم که تمیز باشد)
            // نکته: اگر دیتای مهمی دارید اول بکاپ بگیرید. اینجا فرض بر توسعه است.
            $table->dropColumn('insurance_status');
        });

        Schema::table('guardians', function (Blueprint $table) {
            // ساخت مجدد به صورت بولین
            $table->boolean('insurance_status')->default(0)->after('children_in_house');

            // 3. افزودن کلید خارجی به جدول جدید
            $table->foreignId('insurance_type_id')
                ->nullable() // چون اگر بیمه نداشته باشد این فیلد خالی است
                ->after('insurance_status')
                ->constrained('insurance_types')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropForeign(['insurance_type_id']);
            $table->dropColumn('insurance_type_id');
            $table->dropColumn('insurance_status');
            $table->string('insurance_status')->nullable();
            $table->string('other_insurance')->nullable();
        });
    }

};
