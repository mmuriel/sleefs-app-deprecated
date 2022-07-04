<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Sleefs\Helpers\CustomLogger;
use \Sleefs\Helpers\GraphQL\GraphQLClient;
use \Sleefs\Helpers\Shiphero\ShipheroProductDeleter;
use \Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use \Sleefs\Models\Shopify\Product;



class DeleteAuthorizedShipheroProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sleefs:deleteAuthorizedShipheroProducts {--F|forced : Forces to delete locally the product} {products? : String of comma separated product IDs, for example: 30,45,673}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina remotamente los productos en la plataforma Shiphero.com, que estén marcados como "autorizados" en la app local, el criterio para definir como "autorizado" el borrado de un producto es que el atributo delete_status de una instancia de la clase \Sleefs\Models\Shopify\Products esté definido en valor = 5';

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
    public function handle()
    {
        //------------------------------------------------------------------------
        //1. It handles product IDs options
        //------------------------------------------------------------------------
        $products = array();
        $forced = $this->option('forced');
        $clogger = new CustomLogger("sleefs.log");

        if (null !== $this->argument('products') && preg_match("/([0-9]{1,10}\,{0,1})/",$this->argument('products')))
            $products = preg_split("/\,/",$this->argument('products'));

        if (count($products)==0){
            $productsCollection = Product::where('delete_status',"5")->take(5)->get();
            foreach ($productsCollection as $localProduct){
                array_push($products,$localProduct->id);
            }
        }
        //------------------------------------------------------------------------
        //2. It defines if there are products to be processed.
        //------------------------------------------------------------------------
        if (count($products)==0){
            echo "There aren't products to be delected in local database";
            $clogger->writeToLog ("There aren't products to be delected in local database (comando vía sleefs:deleteAuthorizedShipheroProducts)","INFO");
        }

        //------------------------------------------------------------------------
        //3. It processes the deletion task.
        //------------------------------------------------------------------------
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
        

        $productDeleter = new ShipheroProductDeleter($shipHeroApi);
        
        for ($i=0;$i<count($products);$i++){


            $responsePrdDel = $productDeleter->deleteProductInShiphero($products[$i]);
            if ($responsePrdDel->error){
                //Something went wrong
                $clogger->writeToLog ("Fallo general en el intento de borrado de un producto en shiphero vía comando sleefs:deleteAuthorizedShipheroProducts: ".$responsePrdDel->msg,"ERROR");
            }
            $errorInVarinatsDeletion = false;
            $finalMsg = "";
            foreach ($responsePrdDel->variants as $variant){
                if ($variant->error){
                    if (preg_match("/^Not product with sku ".$variant->sku."/",trim($variant->msg))){
                        $clogger->writeToLog ("La variante con SKU: ".$variant->sku." ya no existe en shiphero. (intento de borrado vía sleefs:deleteAuthorizedShipheroProducts) ".$variant->msg,"INFO");
                    } 
                    elseif (preg_match("/^Unexpected Error/",trim($variant->msg))){
                        $errorInVarinatsDeletion = true;
                        $clogger->writeToLog ("Error desconocido de borrado remoto para el SKU: ".$variant->sku." por favor notificar de borrado manual al admin del sistema. (intento de borrado vía sleefs:deleteAuthorizedShipheroProducts) ".$variant->msg,"ERROR");
                    }   
                    else{

                        
                        $clogger->writeToLog ("Falló el intento de borrado en shiphero para la variante: ".$variant->sku.". ".$variant->msg."(intento de borrado vía sleefs:deleteAuthorizedShipheroProducts)","ERROR");   
                    }
                }
                else{
                    $clogger->writeToLog ("Sku: ".$variant->sku." - Ok"."(intento de borrado vía sleefs:deleteAuthorizedShipheroProducts)","INFO"); 
                }
            }
            $responseStatus = 2;//Parcialmente borrado
            if (!$errorInVarinatsDeletion || $forced){
                $product = Product::where("id",$products[$i])->first();         
                $product->delete_status = 4;
                $product->save();
                $responseStatus = 1;//Completamente borrado
            }
            

            if ($responseStatus == 1)
                $clogger->writeToLog ("El producto ".$products[$i]." fue borrado exitosamente de la plataforma shiphero.com.","INFO"); 
            elseif ($responseStatus == 2) {
                $clogger->writeToLog ("El producto ".$products[$i]." fue borrado parcialmente de la plataforma shiphero.com, se intentará de nuevo porsteriormente, si el problema persiste, consulte con el administrador del sistema.","WARNING"); 
            }

        }
    }

}
