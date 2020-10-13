<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingShipheroAlternIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sh_purchaseorders', function (Blueprint $table) {
            //
            $table->integer("po_id_legacy")->after("po_id")->default(0)->index();
            $table->string("po_id_token",180)->after("po_id")->default(" ")->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sh_purchaseorders', function (Blueprint $table) {
            //
            $table->dropColumn("po_id_legacy");
            $table->dropColumn("po_id_token");
        });
    }
}
