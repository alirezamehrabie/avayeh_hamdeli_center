<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_service_cards', function (Blueprint $table) {
            $table->id();
            // Public landing-page asset path, e.g. images/landing/services/01-hezine-tahsil.png.
            $table->string('image_path');
            $table->string('title');
            // Physical rail on the landing page: 1 = ردیف یک, 2 = ردیف دو.
            $table->unsignedTinyInteger('rail_row')->default(1);
            $table->unsignedInteger('sort_id')->nullable();
            $table->boolean('active_status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rail_row', 'active_status', 'sort_id'], 'landing_service_cards_rail_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_service_cards');
    }
};
