<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_social_worker', function (Blueprint $table) {
            $table->decimal('allocated_quantity', 12, 2)->default(0)->after('social_worker_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_social_worker', function (Blueprint $table) {
            $table->dropColumn('allocated_quantity');
        });
    }
};
