<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('account_id');
            $table->string('payload');
            $table->string('order_number')->nullable();
            $table->bigInteger('coupon_id');
            $table->string('payment_type')->nullable();
            $table->string('payment_payload')->nullable();
            $table->string('payment_payload_value')->nullable();
            $table->double('sub_total');
            $table->double('tax');
            $table->double('discount')->nullable();
            $table->double('total');
            $table->string('payment_status');
            $table->string('status');
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
        Schema::dropIfExists('checkouts');
    }
}
