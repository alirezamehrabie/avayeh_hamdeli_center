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
        Schema::table('family_statuses', function (Blueprint $table) {
            // حذف ستون متنی قدیمی
            $table->dropColumn('guardian_relation');

            // اضافه کردن ستون کلید خارجی
            $table->foreignId('guardian_relation_type_id')
                ->nullable()
                ->after('person_id')
                ->constrained('guardian_relation_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('family_statuses', function (Blueprint $table) {
            $table->dropForeign(['guardian_relation_type_id']);
            $table->dropColumn('guardian_relation_type_id');
            $table->string('guardian_relation')->nullable();
        });
    }

};
