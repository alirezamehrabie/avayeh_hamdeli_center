<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistrictIdToResidencesTable extends Migration
{
    public function up()
    {
        Schema::table('residences', function (Blueprint $table) {
            // 1) اضافه کردن کلید خارجی
            $table->foreignId('district_id')
                ->nullable()
                ->after('residence_status_id') // یا after('person_id') بر اساس نیاز
                ->constrained('districts')
                ->nullOnDelete();

            // 2) حذف ستون متنی قدیمی
            $table->dropColumn('district');
        });
    }

    public function down()
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->string('district')->nullable()->after('residence_status_id');
            $table->dropConstrainedForeignId('district_id');
        });
    }
}
