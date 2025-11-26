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


            // دهک معیشتی خانوار: 1 تا 10
            $table->enum('economic_decile', [
                '1','2','3','4','5',
                '6','7','8','9','10'
            ])
                ->nullable()
                ->comment('دهک معیشتی خانوار: 1 تا 10');
            $table->index('economic_decile');

            // والدین در قید حیات
            $table->enum('living_parents', [
                'پدر',
                'مادر',
                'هر دو (پدر و مادر)'
            ])->nullable();


            // کدام والد فوت شده
            $table->enum('deceased_parent', [
                'پدر',
                'مادر',
                'هر دو (پدر و مادر)'
            ])->nullable();


            // سال و علت فوت
            $table->integer('death_year')->nullable();
            $table->string('death_reason')->nullable();

            // والد جداشده
            $table->enum('divorced_parent', [
                'پدر',
                'مادر',
                'هر دو (پدر و مادر)'
            ])->nullable();


            // والد مجدد ازدواج کرده
            $table->enum('remarried_parent', [
                'پدر',
                'مادر',
                'هر دو (پدر و مادر)'
            ])->nullable();


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
