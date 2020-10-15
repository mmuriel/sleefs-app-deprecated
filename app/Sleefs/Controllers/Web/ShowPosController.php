<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;


use \Sleefs\Models\Shiphero\PurchaseOrder;
use \Sleefs\Views\Shiphero\PurchaseOrderItemListView;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;
use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;

class ShowPosController extends BaseController{



	function showPo(Request $request,$poid){




		$po = PurchaseOrder::whereRaw("po_id='".$poid."'")->first();
		Shiphero::setKey(env('SHIPHERO_APIKEY'));

		$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipheroGqlApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $poextended = $shipheroGqlApi->getExtendedPO($po->po_id_legacy);
        $poextended = $poextended->data->purchase_order->data;
        $poextended->line_items = $poextended->line_items->edges;
        if (isset($poextended->line_items[0])){
            $poextended->vendor_name = $poextended->line_items[0]->node->vendor->name;
            $poextended->vendor_id = $poextended->line_items[0]->node->vendor->id;
        }
        else
        {
 
            return response('Error: '."La PO ".$po->po_id." no incluye productos (line items), no se procesa hasta que se definan estos elementos en sus registros o no existen datos actualizados sobre esta PO",206);
        }




        $poextended->po_date = date("Y-m-d H:i:s",strtotime($poextended->po_date));
        $poextended->created_at = date("Y-m-d H:i:s",strtotime($poextended->created_at));


        //$poextended = Shiphero::getPO($poid);
		if ($po == null){
			return response("There is no PO identified by ".$poid,204);
		}
		else{


			$po->subTotal = 0.0;
			$po->grandTotal = 0.0;
			$po->items = $po->items()->orderBy('name')->get();
			$po->items = $po->items->map(function($updateObj,$key){

				$urlImageGenerator = new ImageUrlBySizeGenerator();
				$poItemListView = new PurchaseOrderItemListView($updateObj);
				$updateObj->poItemListView = $poItemListView->render($urlImageGenerator);
				return $updateObj;

			});

			foreach ($po->items as $item){
				$po->subTotal += (double)($item->price * $item->quantity);
			}

			$po->grandTotal = (double)($po->subTotal + $po->sh_cost);
			return view("podetails",['po'=>$po,'poextended'=>$poextended]);
			
		}
		

	}


}