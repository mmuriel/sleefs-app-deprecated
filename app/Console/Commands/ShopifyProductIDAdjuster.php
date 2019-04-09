<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Sleefs\Models\Shopify\Product;
use \Sleefs\Models\Shopify\Variant;
use \Sleefs\Models\Shopify\ProductImage;
use \Sleefs\Helpers\ShopifyAPI\Shopify;

class ShopifyProductIDAdjuster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShopifyProductIDAdjuster:adjust {--m|quantity=} {--p|page=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Este comando verifica y ajusta (si es necesario) el ID nativo de shopify en la entidad products de la base de datos de la app. Esto para corregir una posible diferencia de valores en los IDs de shopify en diferentes implementaciones de Mysql.';

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

        $qty = $this->option('quantity');
        $page = $this->option('page');

        if ($qty==null)
            $qty = 10;

        if($page==null){

            if (file_exists(storage_path()."/pager.txt")){
                $savedPage = file(storage_path()."/pager.txt");
                $page = trim($savedPage[0]);
                $page++;
            }else{

                $page=1;    
            }
        }

        $offsetIndex = ($page*$qty)-$qty;
        //Recupera los productos locales a analizar
        $localProducts = Product::whereRaw(" 1 ")->orderBy('id')->offset($page)->limit(($qty))->get();
        $totalLocalProducts = $localProducts->count();

        //Recupera los datos remotos de los productos desde shopify
        $shopifyQueryForProductsOptions = 'handle=';
        foreach ($localProducts as $product){
            $shopifyQueryForProductsOptions .= $product->handle.",";
        }
        echo "\nPage: ".$page." | Offset Index: ".$offsetIndex." | Total per page: ".$qty."\n";
        echo "Total elements: ".$localProducts->count()."\n";
        echo "Query: ".$shopifyQueryForProductsOptions."\n";
        //Intancia un objecto tipo Shopify (API) para realizar queries a la tienda.
        $shopify = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));
        $remoteProducts = $shopify->getAllProducts($shopifyQueryForProductsOptions);
        $totalRemoteProducts = count($remoteProducts->products);

        /*
        echo "Total remote products: ".$totalRemoteProducts."\n";
        return 1;
        */
        echo "\n\n\nComparando registros\n\n";
        //print_r($remoteProducts->products);
        
        foreach($localProducts as $index => $localProduct){
            $j = ($totalRemoteProducts - 0) - 1;
            $ctrlDescubierto = false;
            for ($i=0;$i <= round(($totalRemoteProducts  / 2),0,PHP_ROUND_HALF_DOWN) && $j >= round(($totalRemoteProducts  / 2),0,PHP_ROUND_HALF_DOWN);$i++){
                $remotePrdt1 = $remoteProducts->products[$i];
                $remotePrdt2 = $remoteProducts->products[$j];

                if ($localProduct->handle == $remotePrdt1->handle){

                    //Primero borra cualquier producto que pueda generar una colision de 
                    //de IDs shopify duplicados
                    $resDelete = Product::whereRaw(" idsp='(shpfy_".$remotePrdt1->id."' && id != '".$localProduct->id."' ")->delete();



                    echo "\n\n\n---\n[+] ".$remotePrdt1->handle."(Local ID: ".$localProduct->id.")\n";
                    echo "[+] Lo encontró por abajo\n";
                    echo "=================================\n\n";
                    $ctrlDescubierto = true;
                    $localProduct->idsp = "shpfy_".$remotePrdt1->id;
                    $localProduct->save();
                    $this->adjustLocalShopifyProduct($localProduct,$remotePrdt1);
                    break;
                }
                if ($localProduct->handle == $remotePrdt2->handle){

                    //Primero borra cualquier producto que pueda generar una colision de 
                    //de IDs shopify duplicados
                    $resDelete = Product::whereRaw(" idsp='shpfy_".$remotePrdt2->id."' && id != '".$localProduct->id."' ")->delete();


                    echo "\n\n\n---\n[+] ".$remotePrdt2->handle."(Local ID: ".$localProduct->id.")\n";
                    echo "[+] Lo encontró por arriba\n";
                    echo "=================================\n\n";
                    $ctrlDescubierto = true;
                    $localProduct->idsp = "shpfy_".$remotePrdt2->id;
                    $localProduct->save();
                    $this->adjustLocalShopifyProduct($localProduct,$remotePrdt2);
                    break;
                }
                $j = ($totalRemoteProducts - $i) - 1;
            }

            if ($ctrlDescubierto==false){

                echo "\n\n\n---\n[-] ".$localProduct->handle."(Local ID: ".$localProduct->id.")\n";
                echo "[-] Este producto local ya no tiene un producto definido en la tienda...\n";
                echo "=================================\n\n";
                $this->adjustLocalShopifyProduct($localProduct);
                $localProduct->delete();
            }
        }


        $this->saveProcessedPage($page);
    }



    private function adjustLocalShopifyProduct ($localShopifyPrdt,$remoteShopifyPrdt=null){

        echo "\n\nLas siguientes son las imágenes relacionadas con el producto: ".$localShopifyPrdt->handle."\n";
        foreach ($localShopifyPrdt->images as $image){

            echo $image->url."\n";
            echo "Borrando... ".$image->delete()."\n";
        }
        echo "\n\n";
        echo "\n\nLas siguientes son las variantes relacionadas con el producto: ".$localShopifyPrdt->handle."\n";
        foreach ($localShopifyPrdt->variants as $variant){

            echo $variant->title."\n";
            echo "Borrando... ".$variant->delete()."\n";
        }
        echo "\n\n";

        if ($remoteShopifyPrdt!=null){

            //Registra las variantes y las imágenes
            //1. Variantes:
            foreach($remoteShopifyPrdt->variants as $remoteVariant){

                //1. Elimina los posibles IDs duplicados:
                $resDelete = Variant::where('idsp','=',"shpfy_".$remoteVariant->id)->delete();
                echo "Registrando la nueva variante para: ".$remoteVariant->title." (".$remoteVariant->sku.")\n";
                $newVariant = new Variant();
                $newVariant->idsp = "shpfy_".$remoteVariant->id;
                $newVariant->sku = trim($remoteVariant->sku);
                $newVariant->title = $remoteVariant->title;
                $newVariant->idproduct = $localShopifyPrdt->id;
                $newVariant->price = $remoteVariant->price;
                $newVariant->save();

            }
            
            //2. Imagenes:
            foreach($remoteShopifyPrdt->images as $remoteImg){

                echo "Registrando la nueva imagen para: shpfy_".$remoteImg->src." (".$remoteImg->id.")\n";
                $resDelete = ProductImage::where('idsp','=',"shpfy_".$remoteImg->id)->delete();
                $newImage = new ProductImage();
                $newImage->idsp = "shpfy_".$remoteImg->id;
                $newImage->position = $remoteImg->position;
                $newImage->url = $remoteImg->src;
                $newImage->idproducto = $localShopifyPrdt->id;
                $newImage->save();
            }

        }
    }


    private function saveProcessedPage($page){

        $fp = fopen(storage_path()."/pager.txt","w+");
        fwrite($fp,$page);
        fclose($fp);
    }
}
