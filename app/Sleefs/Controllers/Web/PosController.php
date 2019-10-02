<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;


use \Sleefs\Models\Shiphero\PurchaseOrder;
use \Sleefs\Views\Shiphero\PurchaseOrderItemListView;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;
use \mdeschermeier\shiphero\Shiphero;

class PosController extends BaseController{


	function __construct(){

		$this->middleware('auth');

	}


	function index(Request $request){

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
		if ($endDate == 'nd'){
			$endTime = $iniTime + (60 * 60 * 24 * 5);//Endtime is: Initime + 5 days (default value)
			$endDate = date("Y-m-d 23:59:59",$endTime);
		}

		$poid = $request->input("search-po","all");


		if ($poid==''){
			$poid = "all";			
		}



		$user = Auth()->user();

		//Recupera las POUpdates
		if ($poid=='all'){
			$pos = PurchaseOrder::whereRaw("created_at >='".$iniDate."' && created_at <= '".$endDate."'")->get();
		}
		else{
			//$poupdates = PurchaseOrderUpdate::whereRaw("(created_at >='".$iniDate."' && created_at <= '".$endDate."') && shpioid='".$poid."'")->get();
			if ($iniDateRaw=='now'){
				$pos = PurchaseOrder::whereRaw("po_id='".$poid."'")->get();
			}
			else{
				$pos = PurchaseOrder::whereRaw("(created_at >='".$iniDate."' && created_at <= '".$endDate."') && po_id='".$poid."'")->get();	
			}
		}
		
		if (!$pos->isEmpty()){
			//echo "POs no vacio";
			$pos = $pos->map(function($updateObj,$key){

				$updateObj->poListView = new \Sleefs\Views\Shiphero\PurchaseOrderListView($updateObj);
				return $updateObj;

			});
		}
		else{
			//echo "POs vacio";
		}



		//return('Hola desde el controlador principal');
		//return view("index",["programacion"=>$programacion,"timebase"=>$timeBase,"linksArrow"=>$linksArrow,"q"=>$qString]);
		return view("pos",['pos'=>$pos,'searchIniDate'=>$request->input("search-ini-date",""),'searchEndDate'=>$request->input("search-end-date",""),'searchPo'=>$request->input("search-po","")]);
	}




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


	function updatePic(Request $request,$poid){

		return response()->json(['data1' => 'MMMA','data2'=>'NNNNNA']);

	}


}