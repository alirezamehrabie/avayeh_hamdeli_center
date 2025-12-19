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
        Schema::table('support_coverages', function (Blueprint $table) {
            // حذف فیلد متنی قدیمی و اضافه کردن کلید خارجی
            $table->dropColumn('organization_type');
            $table->foreignId('support_organization_id')->nullable()->constrained('support_organizations');
            $table->string('other_organization_name')->nullable()->after('support_organization_id');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
