<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductsToLogicDelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            /*
                Este valor define el borrado logico de un producto, recibe los siguientes valores:
                1. Ok (Producto activo en todas las plataformas)
                2. Borrado en shopify
                3. Borrado en shiphero
                4. BOrrado en ambos.
            */
            $table->enum("delete_status",[1,2,3,4])->after("handle")->default(1)->comment("Este valor define el borrado logico de un producto, recibe los siguientes valores posibles: 1. Ok; 2. Borrado en shopify; 3. Borrado en shiphero; 4. Borrado en todos")->index();
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
            $table->dropColumn("delete_status");
        });
    }
}
