<?php

namespace Sleefs\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\ProductImage;

class ProductDeleteController extends Controller {

	public function __invoke(){

		$prd = json_decode(file_get_contents('php://input'));
		$clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
		$clogger->writeToLog ("Procesando un producto eliminado en shopify: ".json_encode($prd),"INFO");
		$clogger->writeToLog ("ID Producto: "."shpfy_".$prd->id,"INFO");

        $product = Product::where("idsp","=","shpfy_".$prd->id)->first();
        $product->delete_status = 2;
        $product->save();
		return response()->json(["code"=>200,"Message" => "Success"]);

	}	

}
