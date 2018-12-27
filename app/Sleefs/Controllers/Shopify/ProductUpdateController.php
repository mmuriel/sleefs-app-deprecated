<?php

namespace Sleefs\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\ProductImage;

class ProductUpdateController extends Controller {

	public function __invoke(){

		$prd = json_decode(file_get_contents('php://input'));
		$clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
		$clogger->writeToLog ("Procesando un producto adicionado o modificado en shopify: ".json_encode($prd),"INFO");
		$clogger->writeToLog ("ID Producto: ".$prd->id."\nTitle: ".$prd->title."\nSku primera variante: ".$prd->variants[0]->sku,"INFO");

        $product = Product::where("idsp","=",$prd->id)->first();

        if ($product == null){
            $product = new Product();
            $product->idsp = $prd->id;
            $product->title = $prd->title;
            $product->vendor = $prd->vendor;
            $product->product_type = $prd->product_type;
            $product->handle = $prd->handle;
            $product->idsp = $prd->id;
            $product->save();
        }
        else{

            $product->title = $prd->title;
            $product->vendor = $prd->vendor;
            $product->product_type = $prd->product_type;
            $product->handle = $prd->handle;
            //$product->idsp = $prd->id;
			$product->save();
        }


        if (isset($prd->images) && count($prd->images) > 0){

            //return response()->json($prd->images);
            foreach($prd->images as $image){

                if (ProductImage::where("idsp","=",$image->id)->first() == null){
                    $prdImg = new ProductImage();
                    $prdImg->idproducto = $product->id;
                    $prdImg->idsp = $image->id;
                    $prdImg->position = $image->position;
                    $prdImg->url = $image->src;
                    $prdImg->save();
                }
            }

        }


        foreach ($prd->variants as $var){

            if ($var->sku == '' || $var->sku == null ){
                //echo "El SKU del producto: ".$prd->title." es nulo\n";
                $clogger->writeToLog ("El SKU del producto: ".$prd->title." es nulo\n","INFO");
                continue;
            }

            $variant = Variant::where("idsp","=",$var->id)->first();
            if ($variant == null){

                $variant = new Variant();
                if ($var->sku == '' or $var->sku == null){
                    $var->sku = strtolower(preg_replace("/(\ {1,3})/","-",$var->sku));
                }
                $variant->idsp = $var->id;
                $variant->sku = $var->sku;
                $variant->title = $var->title;
                $variant->idproduct = $product->id;
                $variant->price = $var->price;
                $variant->save();
            }
            else{

                $variant->idsp = $var->id;
                $variant->sku = $var->sku;
                $variant->title = $var->title;
                $variant->idproduct = $product->id;
                $variant->price = $var->price;
                $variant->save();
            }
            
        }

        $clogger->writeToLog ("ID Producto: ".$prd->id."\nTitle: ".$prd->title."\nTotal variantes: ".count($prd->variants),"INFO");

		return response()->json(["code"=>200,"Message" => "Success"]);

	}	

}