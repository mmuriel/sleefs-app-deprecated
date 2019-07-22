<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUniqueIndexAndProductTypeTablaShipheroPOItems2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sh_purchaseorder_items', function (Blueprint $table) {
            //
            $table->string('product_type',150)->default('')->index();
            $table->integer('qty_pending')->default(0);
            $table->unique('shid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sh_purchaseorder_items', function (Blueprint $table) {
            //
            $table->dropIndex('product_type');
            $table->dropColumn('product_type');
            $table->dropColumn('qty_pending');
            $table->dropUnique('shid');
        });
    }
}
