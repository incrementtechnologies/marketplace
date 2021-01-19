<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProductsTableAddDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('products', function (Blueprint $table) {
            $table->string('details')->after('status')->default(json_encode(array("solvent" => "", "safety" => "", "formulation" => "", "group"=> '', "active"=> array(), "safety_equipment" => array(), "mixing_order"=> array(), "files"=> array("url"=> '', "title"=> ''))));


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
}
