<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Models\Shiphero\PurchaseOrderUpdateItem;

class SyncerPoItemWarehousePostion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SyncerPoItemWarehousePostion:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca los items de las actualizaciones para determinar la ubicación en bodega, esta información se toma desde la API de ShipHero';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        //
        Shiphero::setKey(env('SHIPHERO_APIKEY'));
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
        


        $updateItems = PurchaseOrderUpdateItem::whereRaw("position='na' && tries <= '".env('SHIPHERO_UPDATEITEM_MAX_TRIES')."'")->get();
        if (count($updateItems)==0){

            echo "No existen items para actualizar la posición en bodega.\n";
            $clogger->writeToLog ("No existen items para actualizar la posición en bodega","INFO");
            return 0;
        }
        foreach ($updateItems as $upItem){
            //echo $upItem->sku."\n";
            $params = array(
                'sku' => $upItem->sku,
            );
            $product = Shiphero::getProduct($params);
            //print_r($product->products->results[0]->warehouses[0]);
            //print_r($product);
            //echo "\n------------------------------\n";

            try {
                if (is_array($product->products->results)){
                    try{
                        if (isset($product->products->results[0]->warehouses[0]->inventory_bin) && preg_match("/[A-Z0-9a-z]{2,15}/",$product->products->results[0]->warehouses[0]->inventory_bin)){
                            $clogger->writeToLog ("Se define la posición en bodega para el item: ".$upItem->sku.", UpdateOrder: ".$upItem->idpoupdate."","INFO");
                            $upItem->position = $product->products->results[0]->warehouses[0]->inventory_bin;
                            $upItem->save();


                            $clogger->writeToLog ("Se define la posición en bodega para el item: ".$upItem->sku.", UpdateOrder: ".$upItem->idpoupdate."","INFO");

                        }
                        else{
                            
                            $upItem->tries++;
                            $upItem->save();
                            $clogger->writeToLog ("No se define aún la posición en bodega para el item: ".$upItem->sku.", UpdateOrder: ".$upItem->idpoupdate.", intento No. ".$upItem->tries,"INFO");
                        }
                    }
                    catch(\Exception $e){
                        echo "Error trying to update inventory position: \n".$e->message();

                    }
                }
            }catch (\Exception $e){
                echo "Error trying to update inventory position to SKU: {$upItem->sku,} \n".$e->message();
                $clogger->writeToLog ("Error trying to update inventory position to SKU: {$upItem->sku,} \n".$e->message(),"ERROR");
            }
        }
    }
}
