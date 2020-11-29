<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaddocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paddocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('merchant_id');
            $table->string('name');
            $table->string('area');
            $table->bigInteger('account_id');
            $table->string('short_description');
            $table->string('note')->nullable();
            $table->string('status');
            $table->integer('arable_area');
            $table->integer('spray_area');
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
        Schema::dropIfExists('paddocks');
    }
}
