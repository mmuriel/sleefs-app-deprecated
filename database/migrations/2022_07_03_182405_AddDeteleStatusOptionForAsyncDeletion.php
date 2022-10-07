<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeteleStatusOptionForAsyncDeletion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
        Schema::table('products', function (Blueprint $table) {
            //
        });
        */
        if (\App::environment() != 'testing'){
            DB::statement("ALTER TABLE products MODIFY COLUMN delete_status ENUM('1', '2', '3', '4', '5') COMMENT 'Este valor define el borrado logico de un producto, recibe los siguientes valores posibles: 1. Ok; 2. Borrado en shopify; 3. Borrado en shiphero; 4. Borrado en todos; 5. Aprobado para borrado asincrónico' ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*
        Schema::table('products', function (Blueprint $table) {
            //
        });
        */
        if (\App::environment() != 'testing'){
            DB::statement("ALTER TABLE products MODIFY COLUMN delete_status ENUM('1', '2', '3', '4') COMMENT 'Este valor define el borrado logico de un producto, recibe los siguientes valores posibles: 1. Ok; 2. Borrado en shopify; 3. Borrado en shiphero; 4. Borrado en todos' ");
        }
    }
}
