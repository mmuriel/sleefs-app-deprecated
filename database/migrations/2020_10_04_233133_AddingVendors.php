<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingVendors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sh_vendors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('idsp',100);
            $table->string('name',140);
            $table->string('legacy_idsp',90);
            $table->string('email',180);
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
        Schema::dropIfExists('sh_vendors');
    }
}
