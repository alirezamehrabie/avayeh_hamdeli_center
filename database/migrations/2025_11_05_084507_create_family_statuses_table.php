<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('family_statuses', function (Blueprint $table) {
            $table->id();

            // ارتباط با جدول people
            $table->unsignedBigInteger('person_id');

            // نوع سرپرست یا نسبت خانوادگی
            $table->enum('guardian_relation', ['پدربزرگ', 'مادربزرگ', 'اقوام', 'سایر'])->nullable();

            // 🔹 دهک معیشتی خانوار
            $table->enum('economic_decile', [
                'دهک اول', 'دهک دوم', 'دهک سوم', 'دهک چهارم', 'دهک پنجم',
                'دهک ششم', 'دهک هفتم', 'دهک هشتم', 'دهک نهم', 'دهک دهم'
            ])->nullable();

            // 🔹 والدین در قید حیات
            $table->enum('living_parents', ['پدر', 'مادر', 'هر دو (پدر و مادر)'])->nullable();

            // 🔹 کدام والد فوت شده
            $table->enum('deceased_parent', ['پدر', 'مادر', 'هر دو (پدر و مادر)'])->nullable();

            // 🔹 سال و علت فوت
            $table->integer('death_year')->nullable();
            $table->string('death_reason')->nullable();

            // 🔹 والد جداشده
            $table->enum('divorced_parent', ['پدر', 'مادر', 'هر دو (پدر و مادر)'])->nullable();

            // 🔹 والد مجدد ازدواج کرده
            $table->enum('remarried_parent', ['پدر', 'مادر', 'هر دو (پدر و مادر)'])->nullable();

            // 🔹 تعداد فرزندان از ازدواج قبلی سرپرست
            $table->integer('children_from_previous_marriage')->nullable();

            // 🔹 وجود معلولیت در والدین
            $table->boolean('has_parent_disability')->default(false);
            $table->text('parent_disability_description')->nullable();

            $table->timestamps();

            // 🔹 کلید خارجی
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_statuses');
    }
};
