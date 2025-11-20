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
        // 1. حذف ستون اضافی از جدول people
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('guardian_role');
        });

        // 2. اطمینان از وجود ستون مناسب در family_statuses
        Schema::table('family_statuses', function (Blueprint $table) {
            // اگر ستون guardian_relation وجود ندارد آن را بسازیم
            // این ستون هم نقش "پدر خانواده" را ذخیره می‌کند هم "پدربزرگ" (بسته به نوع مددجو)
            if (!Schema::hasColumn('family_statuses', 'guardian_relation')) {
                $table->string('guardian_relation')->nullable()->after('person_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('guardian_role')->nullable();
        });

        Schema::table('family_statuses', function (Blueprint $table) {
            $table->dropColumn('guardian_relation');
        });
    }

};
