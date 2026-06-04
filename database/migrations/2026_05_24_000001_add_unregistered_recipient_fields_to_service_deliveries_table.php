<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table) {
            $table->string('national_id', 10)->nullable()->after('guardian_id');
            $table->string('full_name')->nullable()->after('national_id');
            $table->string('mobile', 11)->nullable()->after('full_name');
        });

        DB::table('service_deliveries')
            ->leftJoin('people', 'people.id', '=', 'service_deliveries.person_id')
            ->leftJoin('guardians', 'guardians.id', '=', 'service_deliveries.guardian_id')
            ->update([
                'service_deliveries.national_id' => DB::raw("COALESCE(people.national_id, guardians.national_code)"),
                'service_deliveries.full_name' => DB::raw("
                    COALESCE(
                        NULLIF(TRIM(CONCAT_WS(' ', people.first_name, people.last_name)), ''),
                        NULLIF(TRIM(CONCAT_WS(' ', guardians.first_name, guardians.last_name)), '')
                    )
                "),
                'service_deliveries.mobile' => DB::raw('guardians.guardian_phone_number'),
            ]);

        DB::table('service_deliveries')
            ->whereNull('national_id')
            ->update(['national_id' => '0000000000']);

        DB::table('service_deliveries')
            ->whereNull('full_name')
            ->update(['full_name' => '-']);

        Schema::table('service_deliveries', function (Blueprint $table) {
            $table->string('national_id', 10)->nullable(false)->change();
            $table->string('full_name')->nullable(false)->change();
            $table->index('national_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table) {
            $table->dropIndex(['national_id']);
            $table->dropColumn(['national_id', 'full_name', 'mobile']);
        });
    }
};
