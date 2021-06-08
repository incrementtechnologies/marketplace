<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransferredProductsTableAddBundledSettingQty extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transferred_products', function (Blueprint $table) {
            $table->bigInteger('bundled_setting_qty')->after('product_attribute_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transferred_products', function (Blueprint $table) {
            //
        });
    }
}
