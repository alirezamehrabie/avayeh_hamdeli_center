<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->string('other_account_owner_relation')
                ->nullable()
                ->after('account_owner_relation_id')
                ->comment('نام نسبت در صورتی که گزینه سایر انتخاب شود');
        });
    }

    public function down()
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->dropColumn('other_account_owner_relation');
        });
    }
};
