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


use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\Shiphero\ShipheroProductDeleter;



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



	function ShowRemoteDeletedProducts (Request $request){

		/*
		$ctrlIteration = 0;
		$query = '';
		foreach ($request->input("poupdateitems") as $poUpdateItemId){
			if ($ctrlIteration == 0)
				$query .= "id='".$poUpdateItemId."'";
			else
				$query .= " || id='".$poUpdateItemId."'";

			$ctrlIteration++;
		}
		$poUpdateItems = PurchaseOrderUpdateItem::whereRaw($query)->get();
		*/
		$products = Product::whereRaw("(delete_status = '2' || delete_status = '3')")->get();

		//print_r($products);

		$htmlToPrint = '';
		foreach ($products as $product){

			$htmlSkus = '';
			foreach ($product->variants as $variant){
				$htmlSkus .= "<p>".$variant->sku."</p>";
			}

			$htmlToPrint .= '	<tr id="tr_product_'.$product->id.'">
		<td>
			<input type="checkbox" name="deleted-product-checkbox[]" id="" class="deleted-product-checkbox" value="'.$product->id.'" />
		</td>
		<td class="title">'.$product->title.'</td>
		<td class="skus">'.$htmlSkus.'</td>
		<td class="status"><button data-id="'.$product->id.'" class="btn-delete-one">Borrar</button></td>
	</tr>';
		}

		return view("deleted-remote-products",['htmlToPrint' => $htmlToPrint]);

	}


	function DeleteRemoteProducts (Request $request){

		$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $productDeleter = new ShipheroProductDeleter($shipHeroApi);
        $responsePrdDel = $productDeleter->deleteProductInShiphero($request->input('id'));

        if ($responsePrdDel->error){
        	//Something went wrong
        	$dataResponse = ["error"=>true,"id"=>$request->input('id'),"data"=>["msg"=>$responsePrdDel->msg,"status"=>0]];
        	return response($dataResponse,200);
        }

        $errorInVarinatsDeletion = false;
        $finalMsg = "";

        foreach ($responsePrdDel->variants as $variant){
        	if ($variant->error){
        		$errorInVarinatsDeletion = true;
        		$finalMsg .= "Sku: ".$variant->sku." - Error!<br />\n";
        	}
        	else{
        		$finalMsg .= "Sku: ".$variant->sku." - Ok<br />\n";	
        	}
        }

        $responseStatus = 2;//Parcialmente borrado
        if (!$errorInVarinatsDeletion){
        	$product = Product::where("id",$request->input('id'))->first();        	
        	$product->delete_status = 4;
        	$product->save();
        	$finalMsg = "Product deleted!";
        	$responseStatus = 1;//Completamente borrado
        }

		$dataResponse = ["error"=>false,"id"=>$request->input('id'),"data"=>["msg"=>$finalMsg,"variants"=>$responsePrdDel->variants,"status"=>$responseStatus]];
		return response($dataResponse,200);
		
	}

}