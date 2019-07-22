<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sleefs\Helpers\ShopifyAPI\Shopify;

use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;

class ShopifyGetProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShopifyAdminAPI:getproducts {--p|page=1 : Pagination to call in API} {--s|save=false : It defines if the info must be persisted to database or not}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera los productos de una tienda Shopify';

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
        //
        $spClt = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));
        $opts = $this->options();
        $arguments = $this->arguments();

        $apiCallOpts = 'page='.$opts['page']."&limit=250";
        $data = $spClt->getAllProducts($apiCallOpts);

        foreach ($data->products as $prd){

            $product = Product::where("idsp","=",'shpfy_'.$prd->id)->first();

            if ($product == null){

                $product = new Product();
                $product->idsp = 'shpfy_'.$prd->id;
                $product->title = $prd->title;
                $product->vendor = $prd->vendor;
                $product->product_type = $prd->product_type;
                $product->handle = $prd->handle;
                if ($opts['save'] == 'true'){
                    $product->save();
                }

            }
            else{

                $product->title = $prd->title;
                $product->vendor = $prd->vendor;
                $product->product_type = $prd->product_type;
                $product->handle = $prd->handle;
                $product->idsp = "shpfy_".$prd->id;
                if ($opts['save'] == 'true')
                    $product->save();

            }

            if ($opts['save'] == 'true')
                echo "Procesando y registrando producto: ".$prd->title." (". ($prd->product_type).")\n----\n";
            else
                echo "Procesando (sin registro) producto: ".$prd->title." (". ($prd->product_type).")\n----\n";
            
            foreach ($prd->variants as $var){

                if ($var->sku == '' || $var->sku == null ){

                    echo "El SKU del producto: ".$prd->title." es nulo\n";
                    continue;
                }
                $variant = Variant::where("idsp","=","shpfy_".$var->id)->first();
                if ($variant == null){

                    $variant = new Variant();
                    if ($var->sku == '' or $var->sku == null){
                        $var->sku = strtolower(preg_replace("/(\ {1,3})/","-",$var->sku));
                    }
                    $variant->idsp = "shpfy_".$var->id;
                    $variant->sku = $var->sku;
                    $variant->title = $var->title;
                    $variant->idproduct = $product->id;
                    $variant->price = $var->price;
                    if ($opts['save'] == 'true')
                        $variant->save();
                }
                else{

                    $variant->idsp = "shpfy_".$var->id;
                    $variant->sku = $var->sku;
                    $variant->title = $var->title;
                    $variant->idproduct = $product->id;
                    $variant->price = $var->price;
                    if ($opts['save'] == 'true')
                        $variant->save();

                }

                echo "Var: ".$prd->title." (". ($prd->product_type).")\n----\n";
                
            }

        
        }
        /*
        if ($opts['save'] == 'true'){

            echo "Se debe salvar\n";
        }
        else{

            echo "NO se debe salvar\n";

        }
        */
        
    }
}
