<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;


use \Sleefs\Models\Shiphero\PurchaseOrderUpdate;
use \Sleefs\Models\Shiphero\PurchaseOrderUpdateItem;

class WebController extends BaseController{


	function __construct(){

		$this->middleware('auth');

	}



	function report(Request $request){

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
		$htmlToPrint = '';
		foreach ($poUpdateItems as $poUpdateItem){
			$htmlToPrint .= '	<tr>
		<td>'.$poUpdateItem->poUpdate->idpo.'</td>
		<td>'.$poUpdateItem->sku.'</td>
		<td>'.$poUpdateItem->position.'</td>
		<td>'.$poUpdateItem->quantity.'</td>
		<td>'.$poUpdateItem->qty_before.'</td>
	</tr>';
		}

		return view("report",['htmlToPrint' => $htmlToPrint]);

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
			$poupdates = PurchaseOrderUpdate::whereRaw("created_at >='".$iniDate."' && created_at <= '".$endDate."'")->get();
		}
		else{
			//$poupdates = PurchaseOrderUpdate::whereRaw("(created_at >='".$iniDate."' && created_at <= '".$endDate."') && shpioid='".$poid."'")->get();
			$poRaw = \DB::table("sh_purchaseorders_updates")
				->join("sh_purchaseorders","sh_purchaseorders_updates.idpo","=","sh_purchaseorders.id")
				->whereRaw("sh_purchaseorders.po_id='".$poid."'")->first();

			if ($poRaw != null){

				if ($iniDateRaw=='now'){

					$poupdates = PurchaseOrderUpdate::whereRaw("idpo='".$poRaw->id."'")->get();
				}
				else{
					$poupdates = PurchaseOrderUpdate::whereRaw("(created_at >='".$iniDate."' && created_at <= '".$endDate."') && idpo='".$poRaw->id."'")->get();	
				}
			}
			else{
				$poupdates = new \Illuminate\Database\Eloquent\Collection();
			}
		}
		
		if (!$poupdates->isEmpty()){
			//echo "POs no vacio";
			$poupdates = $poupdates->map(function($updateObj,$key){

				$updateObj->updateView = new \Sleefs\Views\Shiphero\PurchaseOrderUpdateView($updateObj);
				return $updateObj;

			});
		}
		else{
			//echo "POs vacio";
		}



		//return('Hola desde el controlador principal');
		//return view("index",["programacion"=>$programacion,"timebase"=>$timeBase,"linksArrow"=>$linksArrow,"q"=>$qString]);
		return view("index",['poupdates'=>$poupdates,'searchIniDate'=>$request->input("search-ini-date",""),'searchEndDate'=>$request->input("search-end-date",""),'searchPo'=>$request->input("search-po","")]);
	}


}