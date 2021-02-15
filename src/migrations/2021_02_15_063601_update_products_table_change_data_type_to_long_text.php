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
        Schema::table('products', function (Blueprint $table) {
            $table->longText('details')->after('status')->default(json_encode(array("solvent" => "", "safety" => "", "formulation" => "", "group"=> '', "active"=> array(), "safety_equipment" => array(), "mixing_order"=> array(), "files"=> array("url"=> '', "title"=> ''))));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'details'))

        {

            Schema::table('products', function (Blueprint $table)

            {

                $table->dropColumn('details');

            });

        }
    }
}
