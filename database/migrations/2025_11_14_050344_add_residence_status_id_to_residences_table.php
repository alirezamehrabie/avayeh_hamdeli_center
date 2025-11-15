<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidenceStatusIdToResidencesTable extends Migration
{
    public function up()
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->foreignId('residence_status_id')
                ->nullable()
                ->after('person_id')
                ->constrained('residence_status_types')
                ->nullOnDelete();

            $table->dropColumn('residence_status');
        });
    }

    public function down()
    {
        Schema::table('residences', function (Blueprint $table) {
            $table->string('residence_status')->nullable();
            $table->dropConstrainedForeignId('residence_status_id');
        });
    }
}
