<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProductsTableChangeDataTypeToLongText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table)
        {
            $table->dropColumn('details');
        });
            
        Schema::table('products', function (Blueprint $table) {
            $table->longText('details')->after('status')->default(json_encode(array("solvent" => "", "safety" => "", "formulation" => "", "group"=> '', "active"=> [], "safety_equipment" => [], "mixing_order"=> [], "files"=> array("url"=> '', "title"=> ''))));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('products', function (Blueprint $table)
        {
            $table->dropColumn('details');
        });
    }
}
