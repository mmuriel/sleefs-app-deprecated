<?php

namespace Sleefs\Controllers\Shopify;


use App\Http\Controllers\Controller;
use Sleefs\Helpers\CustomLogger;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;

use Sleefs\Helpers\SleefsPdfStickerGenerator;
use setasign\Fpdi\Fpdi;


class OrderCreateController extends Controller {

	public function __invoke(){

		$order = json_decode(file_get_contents('php://input'));
		$clogger = new CustomLogger("sleefs.log");
        $product = '';        
        $variant = '';
        //Wich product types will be accepted
        $productTypesToPrint = array('Back Plate Decal','Visor Skin','Sticker');
		$clogger->writeToLog ("Procesando una orden creada en shopify: ".json_encode($order),"INFO");
        if (count($order->line_items) > 0 )
            foreach ($order->line_items as $index => $lineItem){

                $clogger->writeToLog ("--------------------------","INFO");
                $clogger->writeToLog ("Item Shopify ID: ".json_encode($lineItem->product_id),"INFO");
                //Recupera la variante y el producto
                $product = Product::where("idsp","=","shpfy_".$lineItem->product_id)->first();
                $variant = Variant::where("idsp","=","shpfy_".$lineItem->variant_id)->first();
                if ($product != null && $variant != null){

                    if (in_array($product->product_type,$productTypesToPrint)){

                        //Busca el archivo PDF correspondiente



                        $clogger->writeToLog ("Producto CON PDF para imprimir","INFO");
                        $clogger->writeToLog ("Product App Sleefs: ".$product->title." (".$product->idsp.", ".$product->product_type.")","INFO");
                        $clogger->writeToLog ("Variant App Sleefs: ".$variant->title." (".$variant->idsp.", ".$variant->sku.")","INFO");                        
                    }
                    else{
                        $clogger->writeToLog ("Producto sin PDF para imprimir","INFO");
                        $clogger->writeToLog ("Product App Sleefs: ".$product->title." (".$product->idsp.", ".$product->product_type.")","INFO");
                        $clogger->writeToLog ("Variant App Sleefs: ".$variant->title." (".$variant->idsp.", ".$variant->sku.")","INFO");                           
                    }
                }
            }
            

        //

		return response()->json(["code"=>200,"Message" => "Success"]);

	}	

}
