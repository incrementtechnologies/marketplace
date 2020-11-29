<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBatchPaddockTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_paddock_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('spray_mix_id');
            $table->bigInteger('machine_id');
            $table->bigInteger('merchant_id');
            $table->bigInteger('account_id');
            $table->bigInteger('paddock_plan_task_id');
            $table->double('area', 8, 2);
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
        Schema::dropIfExists('batch_paddock_tasks');
    }
}
