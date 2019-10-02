<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;

use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\ProductImage;
use Sleefs\Models\Shopify\Variant;

use Sleefs\Helpers\ShopifyAPI\Shopify;
use Sleefs\Helpers\CustomLogger;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;

class ProductsController extends BaseController{


	function __construct(){

		$this->middleware('auth');

	}


	function updateProductPic(Request $request){

		$variant = Variant::whereRaw(" sku='".$request->input('sku')."' ")->first();
		if ($variant == null){
			return response(["error"=>true,"message"=>"No variant for this SKU","sku"=>$request->input('sku')],404)->header("Content-Type","json/application");
		}
		//return response()->json(['data1' => 'MMMA','data2'=>'NNNNNA']);
		$product = Product::find($variant->idproduct);
		$shopifyApi = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));


		$productImages = $shopifyApi->getAllImagesProduct($product->idsp);
		$clogger = new CustomLogger("sleefs.log");
		$clogger->writeToLog ("Procesando imagenes en demanda: ".json_encode($productImages),"INFO");

		if (count($productImages->images) == 0){
			return response(["error"=>true,"message"=>"No images for this SKU","sku"=>$request->input('sku')],404)->header("Content-Type","json/application");
		}

		$urlImageGenerator = new ImageUrlBySizeGenerator();
		$arrImagesToRet = [];

		for ($i=0;$i<count($productImages->images);$i++){

			if (ProductImage::where("idsp","=","shpfy_".$productImages->images[$i]->id)->first() == null){
                $prdImg = new ProductImage();
                $prdImg->idproducto = $product->id;
                $prdImg->idsp = "shpfy_".$productImages->images[$i]->id;
                $prdImg->position = $productImages->images[$i]->position;
                $prdImg->url = $productImages->images[$i]->src;
                $prdImg->save();
                array_push($arrImagesToRet,$urlImageGenerator->createImgUrlWithSizeParam($productImages->images[$i]->src,150));
            }

		}
		return response()->json(["error"=>false,"sku"=>$request->input('sku'),"images"=>$arrImagesToRet]);

	}


}