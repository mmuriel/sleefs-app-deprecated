<?php
namespace Sleefs\Controllers\Web;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\User;


use \Sleefs\Models\Shiphero\InventoryReport;
use \Sleefs\Models\Shiphero\InventoryReportItem;
use \mdeschermeier\shiphero\Shiphero;


use \Sleefs\Views\Shiphero\InventoryReportListView;
use \Sleefs\Views\Shiphero\InventoryReportItemListView;

class InventoryReportController extends BaseController{


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



		$user = Auth()->user();

		//Recupera las POUpdates
		$reports = InventoryReport::whereRaw("(created_at >='".$iniDate."' && created_at <= '".$endDate."')")->get();	
		
		if (!$reports->isEmpty()){
			//echo "POs no vacio";
			$reports = $reports->map(function($report,$key){

				$report->inventoryReportListView = new \Sleefs\Views\Shiphero\InventoryReportListView($report);
				return $report;

			});
		}
		else{
			//echo "POs vacio";
		}



		//return('Hola desde el controlador principal');
		//return view("index",["programacion"=>$programacion,"timebase"=>$timeBase,"linksArrow"=>$linksArrow,"q"=>$qString]);
		return view("inventoryreport_index",['reports'=>$reports,'searchIniDate'=>$request->input("search-ini-date",""),'searchEndDate'=>$request->input("search-end-date","")]);
	}




	function showInventoryReport(Request $request,$irid){

		$inventoryReport = InventoryReport::find($irid);
		
		if ($inventoryReport == null){
			return ("There is no inventory report identified by ".$poid);
		}
		else{
			//Ordena por cantidad total de inventario los items
			$inventoryReport->inventoryReportItems = $inventoryReport->inventoryReportItems()->orderBy('total_inventory')->get();
			//Agrega a cada item del inventario un objeto tipo InventoryReportItemListView para renderizar el reporte
			$inventoryReport->inventoryReportItems = $inventoryReport->inventoryReportItems->map(function($irItem,$key){

				$irItemListView = new InventoryReportItemListView($irItem);
				$irItem->irItemListView = $irItemListView->render();
				return $irItem;
			});
			return view("inventoryreport_details",['inventory_report'=>$inventoryReport]);	
		}
	}


	function createReport(Request $request){
		exec("".env('PHP_PATH')." /home/admin/app/artisan inventoryreport:create > /dev/null 2>&1 & echo $!");
		return response()->json(["code"=>200,"Message" => "Good!"]);
	}


}