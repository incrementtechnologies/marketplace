<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBundledProductsAddProductAttributeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bundled_products', function (Blueprint $table) {
            $table->bigInteger('product_attribute_id')->after('product_trace')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bundled_products', function (Blueprint $table) {
            //
        });
    }
}
