<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyingFieldIdspProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            //
            $table->string('idsp',60)->change();
            //$table->dropColumn('idsp');
            //$table->integer('idsp',20);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            //
            //$table->dropColumn('idsp');
            //$table->unsignedInteger('idsp');
            $table->unsignedInteger('idsp')->change();
        });
    }
}
