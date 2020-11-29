<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSprayMixProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spray_mix_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('spray_mix_id');
            $table->bigInteger('product_id');
            $table->double('rate', 8, 3);
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spray_mix_products');
    }
}
