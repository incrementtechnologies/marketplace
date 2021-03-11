<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSprayMixesTableChangeDataTypeToDouble extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spray_mixes', function (Blueprint $table) {
            $table->decimal('application_rate', 8, 2)->change();
            $table->decimal('minimum_rate', 8, 2)->change();
            $table->decimal('maximum_rate', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      
    }
}
