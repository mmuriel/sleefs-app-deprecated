<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;
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

        //Default warehouse ID: V2FyZWhvdXNlOjE2ODQ=

        //Shiphero::setKey(env('SHIPHERO_APIKEY'));
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
        $gqlClt = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClt,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
        


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
                'qtyProducts' => 5,
            );
            //$product = Shiphero::getProduct($params);
            $product = $shipHeroApi->getProducts($params);
            //print_r($product->products->results[0]->warehouses[0]);
            //echo "\n------------------------------\n";
            try {
                if (isset($product->products->results))
                {
                    if (is_array($product->products->results) && count($product->products->results)>0)
                    {
                        try{
                            if (isset($product->products->results[0]->warehouses[0]->inventory_bin) && preg_match("/[A-Z0-9a-z]{2,15}/",$product->products->results[0]->warehouses[0]->inventory_bin)){
                                $clogger->writeToLog ("Se define la posición en bodega para el item: ".$upItem->sku.", UpdateOrder: ".$upItem->idpoupdate."","INFO");
                                $upItem->position = $product->products->results[0]->warehouses[0]->inventory_bin;
                                $upItem->save();

                            }
                            else{
                                
                                $upItem->tries++;
                                $upItem->save();
                                $clogger->writeToLog ("No se define aún la posición en bodega para el item: ".$upItem->sku.", UpdateOrder: ".$upItem->idpoupdate.", intento No. ".$upItem->tries,"INFO");
                            }
                        }
                        catch(\Exception $e){
                            echo "Error trying to update inventory position: \n".$e->getMessage()."\n\n";
                            $clogger->writeToLog ("Error trying to update inventory position: ".$e->getMessage()."\n\n","ERROR");
                        }
                    }
                    else
                    {
                        $upItem->tries = $upItem->tries + 10;
                        $upItem->save();
                        $clogger->writeToLog ("No se encontró un item con SKU: ".$upItem->sku.", en el sistema shiphero.com, por favor verificar la razón en el panel de administración","WARNING");

                        echo "No se encontró un item con SKU: ".$upItem->sku.", en el sistema shiphero.com, por favor verificar la razón en el panel de administración\n";
                    }
                }
                else
                {
                    $error = '';
                    if (isset($product->errors) && isset($product->errors[0]))
                    {
                        $error = $product->errors[0];
                    }

                    if (isset($product->error))
                    {
                        $error = $product->error;
                    }

                    if ($error != '')
                    {
                        echo "Error trying to update inventory position, message: \n".$error->message."\n\n";
                        $clogger->writeToLog ("Error trying to update inventory position, message: ".$error->message."\n\n","ERROR");
                        if (preg_match("/^There are not enough credits to perfom the requested operation/",$error->message))
                        {
                            sleep(15);    
                        }
                    }
                    else
                    {
                        echo "Error trying to update inventory position, message: Error desconocido";
                        $clogger->writeToLog ("Error trying to update inventory position, message: Error desconocido \n\n","ERROR");
                    }
                }
            }catch (\Exception $e){
                echo "Error trying to update inventory position to SKU: {$upItem->sku} \n".$e->getMessage()."\n\n";
                $clogger->writeToLog ("Error trying to update inventory position to SKU: {$upItem->sku} ".$e->getMessage()."\n\n","ERROR");
            }
        }
    }
}
