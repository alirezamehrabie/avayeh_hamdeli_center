<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEducationLevelIdToEducationsTable extends Migration
{
    public function up()
    {
        Schema::table('educations', function (Blueprint $table) {
            // ۱) افزودن ستون foreign key
            $table->foreignId('education_level_id')
                ->nullable()
                ->after('education_level')
                ->constrained('education_levels')
                ->nullOnDelete();

            // ۲) (اختیاری) اگر داده‌های قبلی دارید و می‌خواهید مپ کنید
            // در اینجا می‌توانید مقادیر رشته‌ای را به آی‌دی‌ها تبدیل کنید:
            // DB::table('educations')->get()->each(function($row) {
            //     $id = DB::table('education_levels')
            //           ->where('name', $row->education_level)
            //           ->value('id');
            //     DB::table('educations')
            //       ->where('id', $row->id)
            //       ->update(['education_level_id' => $id]);
            // });

            // ۳) حذف ستون رشته‌ای قدیمی
            $table->dropColumn('education_level');
        });
    }

    public function down()
    {
        Schema::table('educations', function (Blueprint $table) {
            // برگردانی: اول ستون رشته‌ای مجدداً اضافه شود
            $table->string('education_level')->after('major');
            // سپس foreign key را بیندازیم
            $table->dropConstrainedForeignId('education_level_id');
        });
    }
}
