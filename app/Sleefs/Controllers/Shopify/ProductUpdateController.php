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
		$clogger->writeToLog ("ID Producto: "."shpfy_".$prd->id."\nTitle: ".$prd->title."\nSku primera variante: ".$prd->variants[0]->sku,"INFO");

        $product = Product::where("idsp","=","shpfy_".$prd->id)->first();

        if ($product == null){
            $product = new Product();
            $product->idsp = "shpfy_".$prd->id;
            $product->title = $prd->title;
            $product->vendor = $prd->vendor;
            $product->product_type = $prd->product_type;
            $product->handle = $prd->handle;
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

	/*
            Elimina primero todas las imágenes asociadas al producto, para evitar mantener imágenes
            ya no válidas o existentes
        */

        ProductImage::where('idproducto','=',$product->id)->delete();

        /*
            Registra las imágenes
        */


        /*
            Elimina primero todas las imágenes asociadas al producto, para evitar mantener imágenes
            ya no válidas o existentes
        */

        ProductImage::where('idproducto','=',$product->id)->delete();

        /*
            Registra las imágenes
        */

        if (isset($prd->images) && count($prd->images) > 0){

            //return response()->json($prd->images);
            foreach($prd->images as $image){

                if (ProductImage::where("idsp","=","shpfy_".$image->id)->first() == null){
                    $prdImg = new ProductImage();
                    $prdImg->idproducto = $product->id;
                    $prdImg->idsp = "shpfy_".$image->id;
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
                $variant->save();
            }
            else{

                $variant->idsp = "shpfy_".$var->id;
                $variant->sku = $var->sku;
                $variant->title = $var->title;
                $variant->idproduct = $product->id;
                $variant->price = $var->price;
                $variant->save();
            }
            
        }

        $clogger->writeToLog ("ID Producto: "."shpfy_".$prd->id."\nTitle: ".$prd->title."\nTotal variantes: ".count($prd->variants),"INFO");

		return response()->json(["code"=>200,"Message" => "Success"]);

	}	

}
