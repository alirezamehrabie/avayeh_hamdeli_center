<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_names', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique();
            $table->foreignId('service_name_id')->constrained('service_names')->cascadeOnDelete();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->enum('service_type', ['individual', 'family']);
            $table->text('description')->nullable();
            $table->decimal('total_quantity', 12, 2);
            $table->string('service_unit');
            $table->unsignedBigInteger('value_per_unit');
            $table->unsignedBigInteger('total_service_value');
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->date('distribution_start_date');
            $table->date('distribution_end_date');
            $table->string('priority')->nullable();
            $table->enum('status', ['draft', 'approved', 'in_distribution', 'completed'])->default('draft');
            $table->text('status_notes')->nullable();
            $table->decimal('quantity_delivered', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('service_social_worker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('social_worker_id')->constrained('social_workers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['service_id', 'social_worker_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('service_social_worker');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('service_names');
    }
};
