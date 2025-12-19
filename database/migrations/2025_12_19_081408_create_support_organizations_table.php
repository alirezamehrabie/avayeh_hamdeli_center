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
        Schema::create('support_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام نهاد
            $table->string('slug')->unique(); // برای شناسایی راحت‌تر "خیریه دیگر" در کد
            $table->timestamps();
        });

        // درج مقادیر پایه مستقیماً در میگریشن برای اطمینان از وجود داده‌ها
        DB::table('support_organizations')->insert([
            ['name' => 'کمیته امداد امام خمینی', 'slug' => 'emdad'],
            ['name' => 'سازمان بهزیستی کشور', 'slug' => 'behzisti'],
            ['name' => 'انجمن حمایت از معلولین', 'slug' => 'disability_support'],
            ['name' => 'انجمن حمایت از زندانیان', 'slug' => 'prisoners_support'],
            ['name' => 'خیریه دیگر', 'slug' => 'other'],
        ]);
    }
};
