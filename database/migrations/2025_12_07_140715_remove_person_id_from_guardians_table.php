<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // برای استفاده از DB::select

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // اطمینان حاصل کنید که ستون 'person_id' وجود دارد قبل از تلاش برای حذف آن
            if (Schema::hasColumn('guardians', 'person_id')) {
                // ابتدا کلید خارجی را حذف کنید، اگر وجود داشته باشد
                // نام کلید خارجی ممکن است متفاوت باشد. یک الگوی رایج: 'table_name_column_name_foreign'
                // با این حال، استفاده از dropForeign با آرایه نام ستون امن‌تر است.
                // اگر لاراول نام پیش‌فرض را استفاده کرده باشد:
                $table->dropForeign(['person_id']); // این کلید خارجی را حذف می‌کند

                // اگر به هر دلیلی کلید خارجی با نام سفارشی ایجاد شده بود، می‌توانید آن را به صورت دستی پیدا و حذف کنید:
                /*
                $foreignKeys = DB::select(
                    "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guardians'
                     AND COLUMN_NAME = 'person_id' AND REFERENCED_TABLE_NAME IS NOT NULL;"
                );
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
                */

                // سپس ستون را حذف کنید
                $table->dropColumn('person_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            // اگر نیاز به بازگرداندن migration باشد، ستون 'person_id' را دوباره اضافه می‌کنیم
            // فرض بر این است که قبلاً یک کلید خارجی به جدول 'people' بوده است.
            // به طور پیش‌فرض NOT NULL است. اگر باید NULLable باشد، '.nullable()' را اضافه کنید.
            $table->foreignId('person_id')->constrained()->after('id'); // بعد از 'id' قرار می‌گیرد
        });
    }
};
