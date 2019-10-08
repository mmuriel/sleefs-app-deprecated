<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddingShippingAndHandlingCostFieldToPOs extends Migration
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
            $table->double('sh_cost',8,2)->default(0.00)->after('fulfillment_status');
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
            $table->dropColumn('sh_cost');
        });
    }
}
