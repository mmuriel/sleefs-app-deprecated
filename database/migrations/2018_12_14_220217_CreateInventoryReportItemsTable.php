<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryReportItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sh_inventoryreport_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('idreporte')->index(); 
            $table->string('label',100)->index();
            $table->integer('total_inventory');
            $table->integer('total_on_order');
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
        Schema::dropIfExists('sh_inventoryreport_items');
    }
}
