<?php

use Illuminate\Http\Request;

use \Sleefs\Controllers\Shiphero\PurchaseOrderWebHookEndPointController;
use \Sleefs\Controllers\Shopify\OrderCreateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


/*

Route::post('/sh', function (Request $request) {
	
	$entityBody = file_get_contents('php://input');
	//print_r(json_decode($entityBody));
	$clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
	$clogger->writeToLog ($entityBody,"INFO");
	return response()->json(["DATA" => json_decode($entityBody)]);
	//return response($entityBody);
	//return response()->json(["code"=>200,"Message" => "Success"]);

});

*/
Route::post('/sh','\Sleefs\Controllers\Shiphero\PurchaseOrderWebHookEndPointController');


//Route::post('/sp/products/add','\Sleefs\Controllers\Shopify\ProductAddController');
Route::post('/sp/products/update','\Sleefs\Controllers\Shopify\ProductUpdateController');
Route::post('/sp/orders','\Sleefs\Controllers\Shopify\OrderCreateController');
/*

	Rutas para Test clase CURL con Phpunit

*/

Route::post('/tests/curl',function(Request $req){

	$payload = json_decode(file_get_contents('php://input'));
	return json_encode($payload);

});

Route::put('/tests/curl',function(Request $req){

	$payload = json_decode(file_get_contents('php://input'));
	return json_encode($payload);

});

Route::delete('/tests/curl',function(Request $req){

	$payload = json_decode(file_get_contents('php://input'));
	return json_encode($payload);

});

Route::get('/tests/curl/{secondchecker?}',function(Request $req,$secondchecker=null){

	$checker = $req->checker;
	if (isset ($secondchecker) && $secondchecker != null)
		return "1. ".$checker." - ".$secondchecker;
	else
		return "2. ".$checker;
});
