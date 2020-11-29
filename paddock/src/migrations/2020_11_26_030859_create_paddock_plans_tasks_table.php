<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaddockPlansTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paddock_plans_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('paddock_plan_id');
            $table->bigInteger('paddock_id');
            $table->string('category');
            $table->date('due_date');
            $table->string('nickname');
            $table->bigInteger('spray_mix_id');
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
        Schema::dropIfExists('paddock_plans_tasks');
    }
}
