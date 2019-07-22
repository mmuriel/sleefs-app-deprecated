<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TablaShipheroPOs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sh_purchaseorders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('po_id');
            $table->string('po_number',120);
            $table->dateTime('po_date');
            $table->string('fulfillment_status',140);
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
        Schema::dropIfExists('sh_purchaseorders');
    }
}
