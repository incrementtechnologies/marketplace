<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePaddocksAddDeletedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paddocks', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('paddock_plans', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('paddock_plans_tasks', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('machines', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('spray_mixes', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('batches', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
        Schema::table('crops', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
