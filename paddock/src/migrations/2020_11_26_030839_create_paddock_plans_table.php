<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaddockPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paddock_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('paddock_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->bigInteger('crop_id');
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
        Schema::dropIfExists('paddock_plans');
    }
}
