<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingQtyBeforeToUpdateItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sh_purchaseorders_updates_items', function (Blueprint $table) {
            //
            $table->integer('qty_before')->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sh_purchaseorders_updates_items', function (Blueprint $table) {
            //
            $table->dropColumn('qty_before');
        });
    }
}
