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
            // ارتباط با جدول people
            $table->foreignId('person_id')
                ->constrained('people')
                ->onDelete('cascade');

            // رابطه سرپرست (ارجاع به جدول انواع نسبت)
            $table->foreignId('guardian_relation_type_id')
                ->nullable()
                ->constrained('guardian_relation_types')
                ->nullOnDelete();


            $table->integer('economic_decile')->nullable();

            // --- اصلاح شده: وضعیت حیات والدین (انگلیسی) ---
            $table->enum('living_parents', [
                'both_alive',   // هر دو در قید حیات
                'father_dead',  // پدر فوت شده
                'mother_dead',  // مادر فوت شده
                'both_dead'     // هر دو فوت شده
            ])->nullable();


            // کدام والد فوت شده
            $table->enum('deceased_parent', ['father', 'mother', 'both'])->nullable();


            // سال و علت فوت
            $table->integer('death_year')->nullable();
            $table->string('death_reason')->nullable();

            // والد جداشده

            // --- اصلاح شده: وضعیت طلاق (انگلیسی) ---
            $table->enum('divorced_parent', [
                'none',     // خیر (طلاق نگرفته‌اند)
                'divorced'  // بله (طلاق گرفته‌اند)
            ])->nullable()->default('none');


            // والد مجدد ازدواج کرده
            $table->enum('remarried_parent', ['none', 'father', 'mother', 'both'])
                ->nullable()
                ->default('none');


            // تعداد فرزندان از ازدواج قبلی سرپرست
            $table->integer('children_from_previous_marriage')->nullable();
            // وجود معلولیت در والدین
            $table->boolean('has_parent_disability')->default(false);
            $table->text('parent_disability_description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_statuses');
    }
};
