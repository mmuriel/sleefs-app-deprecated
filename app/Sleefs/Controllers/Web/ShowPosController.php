<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;


use \Sleefs\Models\Shiphero\PurchaseOrder;
use \Sleefs\Views\Shiphero\PurchaseOrderItemListView;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;
use \mdeschermeier\shiphero\Shiphero;

class ShowPosController extends BaseController{



	function showPo(Request $request,$poid){




		$po = PurchaseOrder::whereRaw("po_id='".$poid."'")->first();
		Shiphero::setKey(env('SHIPHERO_APIKEY'));
        $poextended = Shiphero::getPO($poid);
		if ($po == null){
			return ("There is no PO identified by ".$poid);
		}
		else{



			$po->items = $po->items()->orderBy('name')->get();
			$po->items = $po->items->map(function($updateObj,$key){

				$urlImageGenerator = new ImageUrlBySizeGenerator();
				$poItemListView = new PurchaseOrderItemListView($updateObj);
				$updateObj->poItemListView = $poItemListView->render($urlImageGenerator);
				return $updateObj;

			});


			return view("podetails",['po'=>$po,'poextended'=>$poextended->po->results]);
			
		}
		

	}


}