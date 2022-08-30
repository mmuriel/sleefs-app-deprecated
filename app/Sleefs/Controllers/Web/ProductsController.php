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
		$shopifyApi = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));


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


		$iniDate = $request->input("search-ini-date","now");
		$iniDateRaw = '';
		if ($iniDate == '' || $iniDate=='now'){
			$iniDate = 'now';
			$iniDateRaw = 'now';
		}

		$iniTime = strtotime($iniDate);
		if ($iniDate=='now'){
			$iniTime = $iniTime - (60 * 60 * 24 * 5);//Initime is: now time - 5 days (default value)
			$iniDate = date("Y-m-d 00:00:00",$iniTime);
		}


		$endDate = $request->input("search-end-date","nd");	
		$endDateRaw = '';
		if ($endDate == '' || $endDate=='nd'){

			$endDate = 'nd';
			$endDateRaw = 'nd';

		}
		else{
			$endDate = $endDate." 23:59:59";
		}
		if ($endDate == 'nd'){
			$endTime = $iniTime + (60 * 60 * 24 * 10);//Endtime is: Initime + 5 days (default value)
			$endDate = date("Y-m-d 23:59:59",$endTime);
		}

		$products = Product::whereRaw("(delete_status = '2' || delete_status = '3' || delete_status = '5') && (updated_at >= '".$iniDate."' && updated_at <= '".$endDate."')")->get();

		//print_r($products);

		$htmlToPrint = '';
		foreach ($products as $product){

			$htmlSkus = '';
			foreach ($product->variants as $variant){
				$htmlSkus .= "<p>".$variant->sku."</p>";
			}

			if ($product->delete_status == '5'){

				$htmlToPrint .= '	<tr id="tr_product_'.$product->id.'" class="product-deleted--processing">
			<td>
				
			</td>
			<td class="title">'.$product->title.'</td>
			<td class="skus">'.$htmlSkus.'</td>
			<td class="status">Esperando para ser borrado por el backend</td>
		</tr>';
			}
			else{
				$htmlToPrint .= '	<tr id="tr_product_'.$product->id.'">
			<td>
				<input type="checkbox" name="deleted-product-checkbox[]" id="" class="deleted-product-checkbox" value="'.$product->id.'" />
			</td>
			<td class="title">'.$product->title.'</td>
			<td class="skus">'.$htmlSkus.'</td>
			<td class="status"><button data-id="'.$product->id.'" class="btn-delete-one">Borrar</button></td>
		</tr>';
			}
		}

		return view("deleted-remote-products",['htmlToPrint' => $htmlToPrint,'searchIniDate'=>$request->input("search-ini-date",""),'searchEndDate'=>$request->input("search-end-date","")]);

	}


	function DeleteRemoteProducts (Request $request){


		if ($request->input('delete_type') == 'async'){
			$product = Product::find($request->input('id'));
			$product->delete_status = 5;
        	$product->save();

        	$dataResponse = ["error"=>false,"id"=>$request->input('id'),"data"=>["msg"=>"Esperando para ser borrado por el backend","variants"=>[],"status"=>"5"]];
			return response($dataResponse,200);
		}

		$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
        $clogger = new CustomLogger("sleefs.log");

        $productDeleter = new ShipheroProductDeleter($shipHeroApi);
        $responsePrdDel = $productDeleter->deleteProductInShiphero($request->input('id'));

        if ($responsePrdDel->error){
        	//Something went wrong
        	$dataResponse = ["error"=>true,"id"=>$request->input('id'),"data"=>["msg"=>$responsePrdDel->msg,"status"=>0]];
        	$clogger->writeToLog ("Fallo general en el intento de borrado en shiphero: ".$responsePrdDel->msg,"ERROR");
        	return response($dataResponse,200);
        }

        $errorInVarinatsDeletion = false;
        $finalMsg = "";

        foreach ($responsePrdDel->variants as $variant){
        	if ($variant->error){
        		if (preg_match("/^Not product with sku ".$variant->sku."/",trim($variant->msg))){
        			$finalMsg .= "Sku: ".$variant->sku." - Doesn't exist in shiphero!<br />\n";
        			$clogger->writeToLog ("La variante con SKU: ".$variant->sku." ya no existe en shiphero. ".$variant->msg,"INFO");
        		}	
        		else{

        			$errorInVarinatsDeletion = true;
	        		$finalMsg .= "Sku: ".$variant->sku." - Error!<br />\n";
	        		$clogger->writeToLog ("Falló el intento de borrado en shiphero para la variante: ".$variant->sku.". ".$variant->msg,"ERROR");	
        		}
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
        	if (preg_match("/Error!|exist in shiphero!/",$finalMsg))
        		$finalMsg = "Product deleted! But...<br />".$finalMsg;
        	else
        		$finalMsg = "Product deleted!";
        	$responseStatus = 1;//Completamente borrado
        }

		$dataResponse = ["error"=>false,"id"=>$request->input('id'),"data"=>["msg"=>$finalMsg,"variants"=>$responsePrdDel->variants,"status"=>$responseStatus]];
		return response($dataResponse,200);
		
	}

}