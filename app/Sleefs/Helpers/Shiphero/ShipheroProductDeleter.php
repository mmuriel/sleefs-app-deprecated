<?php
namespace Sleefs\Helpers\Shiphero;

use \Sleefs\Models\Shopify\Product;
use \Sleefs\Models\Shopify\Variant;
use \Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;

class ShipheroProductDeleter{

	private $shipheroGqlApi;

	public function __construct(ShipheroGQLApi $shipheroGqlApi) {
		$this->shipheroGqlApi = $shipheroGqlApi;
	}

	/**
	 * This method deletes a products in shiphero platform by identifying
	 * local variants related to a product, then calling over shiphero's GQL API
	 * a remote procedure to delete those variants in shiphero's side.
	 *
	 * @param int $idLocalProduct This is a local product ID 
	 * 
	 * @return mixed (stdClass) $objToReturn 
	 * 
	 */
	public function deleteProductInShiphero(int $idLocalProduct){

		$objToReturn = new \stdClass();
		$objToReturn->error = false;

		//It looks for the product by ID
		$product = Product::whereRaw("id = '".$idLocalProduct."'")->first();
		if ($product == null){
			$objToReturn->error = true;
			$objToReturn->msg = "No product found for ID: ".$idLocalProduct;
			return $objToReturn;
		}

		$objToReturn->variants = array();
		foreach ($product->variants as $variant){
			$variantDelResponse = new \stdClass();
			$variantDelResponse->id = $variant->id;
			$variantDelResponse->sku = $variant->sku;
			$variantDelResponse->error = false;
			$delResponse = $this->shipheroGqlApi->deleteProduct($variant->sku);
			if (isset($delResponse->errors)){
				$variantDelResponse->error = true;
				$variantDelResponse->msg = $delResponse->errors[0]->message;
			}
			else{
			$variantDelResponse->msg = $delResponse->data->product_delete->request_id." - ".$delResponse->data->product_delete->complexity;
			}
			array_push($objToReturn->variants,$variantDelResponse);
		}

		return $objToReturn;

	}


}