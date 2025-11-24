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
            $table->dropColumn('account_holder_relation');

            $table->foreignId('account_owner_relation_id')
                ->nullable()
                ->after('has_own_account') // مکان قرارگیری ستون
                ->constrained('account_owner_relations')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('bank_infos', function (Blueprint $table) {
            $table->dropForeign(['account_owner_relation_id']);
            $table->dropColumn('account_owner_relation_id');
            $table->string('account_holder_relation')->nullable();
        });
    }

};
