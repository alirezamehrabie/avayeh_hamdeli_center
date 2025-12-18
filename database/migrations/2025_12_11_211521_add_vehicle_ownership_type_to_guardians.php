<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('vehicle_ownership_type')
                ->nullable()
                ->after('vehicle_type_id'); // دقیقاً بعد از نوع وسیله نقلیه
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn('vehicle_ownership_type');
        });
    }
};
