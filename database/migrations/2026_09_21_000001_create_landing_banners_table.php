<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_banners', function (Blueprint $table) {
            $table->id();
            // Public landing-page asset path, e.g. images/landing/slide-1.jpg (served straight from /public, no auth).
            $table->string('image_path');
            $table->string('alt_text');
            $table->string('link_url')->nullable();
            $table->unsignedInteger('sort_id')->nullable();
            $table->boolean('active_status')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['active_status', 'sort_id'], 'landing_banners_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_banners');
    }
};
