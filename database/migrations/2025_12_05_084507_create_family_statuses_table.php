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


            $table->enum('deceased_parent', ['father', 'mother', 'both'])->nullable();

            // والد مجدد ازدواج کرده
            $table->enum('remarried_parent', ['none', 'father', 'mother', 'both'])
                ->nullable()
                ->default('none');



            $table->integer('children_from_previous_marriage')->nullable();

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
