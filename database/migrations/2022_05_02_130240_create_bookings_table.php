<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->integer('hotel_id');
            $table->integer('status_id');
            $table->integer('user_id')->nullable();
            $table->string('quest_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('comment')->nullable();
            $table->integer('adult_count');
            $table->integer('child_count')->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('count_nights');
            $table->integer('discount');
            $table->integer('total_price');
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
        Schema::dropIfExists('bookings');
    }
}
