<?php

namespace Sleefs\Controllers\Shopify;

use App\Http\Controllers\Controller;

class ProductWebHookController extends Controller{
	

	public function __invoke(){

		return response()->json(["code"=>200,"Message" => "Success"]);

	}


}