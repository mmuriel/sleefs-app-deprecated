<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMOndayPulseModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mon_pulses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idpo')->index();
            $table->string('idmonday',50)->index();
            $table->string('name',20)->index();
            $table->string('mon_board',50);
            $table->string('mon_group',50);
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
        Schema::dropIfExists('mon_pulses');
    }
}
