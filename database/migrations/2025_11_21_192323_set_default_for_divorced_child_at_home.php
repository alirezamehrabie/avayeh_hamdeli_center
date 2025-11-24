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
            // تغییر ستون برای داشتن مقدار پیش‌فرض
            $table->string('divorced_child_at_home')->default('ندارد')->change();
        });
    }

    public function down()
    {
        Schema::table('guardians', function (Blueprint $table) {
            // بازگشت به حالت قبل (بدون پیش‌فرض)
            $table->string('divorced_child_at_home')->nullable()->change();
        });
    }
};
