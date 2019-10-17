<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBundledProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up()
    {
        Schema::create('bundled_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->bigInteger('account_id');
            $table->bigInteger('product_id');
            $table->bigInteger('bundled_trace');
            $table->bigInteger('product_trace');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bundled_products');
    }
}
