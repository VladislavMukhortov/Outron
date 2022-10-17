<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('status_id');
            $table->integer('type_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('region_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->string('address')->nullable();
            $table->string('coordinates')->nullable();
            $table->integer('distance_city')->nullable();
            $table->text('detailed_route')->nullable();
            $table->text('conditions')->nullable();
            $table->integer('season_id')->nullable();
            $table->integer('min_days')->nullable();
            $table->integer('check_in_hour')->nullable();
            $table->integer('check_out_hour')->nullable();
            $table->integer('user_id');
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
        Schema::dropIfExists('hotels');
    }
}
