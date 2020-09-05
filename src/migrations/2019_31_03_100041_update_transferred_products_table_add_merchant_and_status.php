<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransferredProductsTableAddMerchantAndStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transferred_products', function (Blueprint $table) {
            $table->bigInteger('merchant_id')->after('product_id')->nullable();
            $table->string('status')->after('merchant_id')->default('active')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transferred_products');
    }
}
