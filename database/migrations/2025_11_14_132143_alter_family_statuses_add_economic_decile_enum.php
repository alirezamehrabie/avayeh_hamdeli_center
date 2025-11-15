<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            // 1) در صورت وجود ستون قدیمیِ integer یا foreign key، آن را حذف کنید:
            if (Schema::hasColumn('family_statuses', 'economic_decile_id')) {
                $table->dropConstrainedForeignId('economic_decile_id');
            }
            if (Schema::hasColumn('family_statuses', 'economic_decile')) {
                $table->dropColumn('economic_decile');
            }

            // 2) افزودن ستون ENUM
            $table->enum('economic_decile', [
                '1','2','3','4','5',
                '6','7','8','9','10'
            ])
                ->nullable()
                ->after('person_id')
                ->comment('دهک معیشتی خانوار: 1 تا 10');

            // 3) ایندکس برای سرعت فیلتر
            $table->index('economic_decile');
        });
    }

    public function down(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            $table->dropIndex(['economic_decile']);
            $table->dropColumn('economic_decile');
        });
    }
};
