<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_infos', function (Blueprint $table) {
            $table->id();

            // ارجاع به فرد (people) با قید unique
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->onDelete('cascade');

            // آیا فرد حساب بانکی دارد؟
            $table->boolean('has_own_account')
                ->default(false)
                ->nullable();

            // رابطه مالک حساب: ارجاع به جدول account_owner_relations
            $table->foreignId('account_owner_relation_id')
                ->nullable()
                ->constrained('account_owner_relations')
                ->nullOnDelete();

            // در صورت انتخاب "سایر" برای نسبت مالک حساب
            $table->string('other_account_owner_relation')
                ->nullable()
                ->comment('نام نسبت در صورتی که گزینه سایر انتخاب شود');

            // شماره کارت (16 رقم)
            $table->string('card_number', 16)
                ->nullable();

            // شماره شبا (IR + 24 رقم)
            $table->string('sheba_number', 26)
                ->nullable();

            // شماره کارت یارانه (16 رقم)
            $table->string('subsidy_card_number', 16)
                ->nullable();

            // شماره شبا یارانه (IR + 24 رقم)
            $table->string('subsidy_sheba_number', 26)
                ->nullable();

            // ارجاع به جدول banks به جای ذخیره نام بانک
            $table->foreignId('bank_id')
                ->nullable()
                ->constrained('banks')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_infos');
    }
};
