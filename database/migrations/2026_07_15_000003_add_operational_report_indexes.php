<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->index(
                ['person_id', 'service_id', 'service_category_id', 'delivered_at'],
                'sd_person_service_cat_date_idx'
            );
            $table->index(
                ['guardian_id', 'service_id', 'service_category_id', 'delivered_at'],
                'sd_guardian_service_cat_date_idx'
            );
        });

        Schema::table('beneficiary_case_records', function (Blueprint $table): void {
            $table->index(
                ['person_id', 'record_type', 'recorded_at'],
                'bcr_person_type_date_idx'
            );
        });

        Schema::table('activity_attendances', function (Blueprint $table): void {
            $table->index(
                ['person_id', 'status', 'checked_in_at'],
                'aa_person_status_date_idx'
            );
        });

        Schema::table('service_social_worker', function (Blueprint $table): void {
            $table->index(
                ['social_worker_id', 'service_id', 'service_category_id'],
                'ssw_worker_service_cat_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('service_deliveries', function (Blueprint $table): void {
            $table->dropIndex('sd_person_service_cat_date_idx');
            $table->dropIndex('sd_guardian_service_cat_date_idx');
        });

        Schema::table('beneficiary_case_records', function (Blueprint $table): void {
            $table->dropIndex('bcr_person_type_date_idx');
        });

        Schema::table('activity_attendances', function (Blueprint $table): void {
            $table->dropIndex('aa_person_status_date_idx');
        });

        Schema::table('service_social_worker', function (Blueprint $table): void {
            $table->dropIndex('ssw_worker_service_cat_idx');
        });
    }
};
