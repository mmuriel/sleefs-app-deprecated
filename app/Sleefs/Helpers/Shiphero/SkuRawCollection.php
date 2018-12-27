<?php

namespace Sleefs\Helpers\Shiphero;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

class SkuRawCollection extends \Illuminate\Support\Collection {


    public function __construct() {

        parent::__construct();
    }


    /**
    *
    *    This method adds to a collection all elements from a data structure in json format like this:
    *    [
    *            {
    *                "sku": "111",
    *                "kit_components": [],
    *                "warehouses": [
    *                    {
    *                        "available": "0",
    *                        "inventory_bin": " ",
    *                        "inventory_overstock_bin": " ",
    *                        "backorder": "0",
    *                        "warehouse": "Primary",
    *                        "on_hand": "0",
    *                        "allocated": "0"
    *                    }
    *                ],
    *                "build_kit": 0,
    *                "value": "0.0700",
    *                "kit": 0
    *            },
    *            {
    *                "sku": "123",
    *                "kit_components": [],
    *                "warehouses": [
    *                    {
    *                        "available": "0",
    *                        "inventory_bin": "",
    *                        "inventory_overstock_bin": "",
    *                        "backorder": "0",
    *                        "warehouse": "Primary",
    *                        "on_hand": "0",
    *                        "allocated": "0"
    *                    }
    *                ],
    *                "build_kit": 0,
    *                "value": "10.0000",
    *                "kit": 0
    *            }
    *        ]
    *
    *    @param json decoded object $jsonRawProducts A json decoded object,  structured as the shown above example
    *
    *    @return void
    *
    */

    public function addElementsFromShipheroApi($jsonRawProducts){

        foreach ($jsonRawProducts as $rawProduct){
            if (! $this->get($rawProduct->sku)){
                $tmpData = array('qty'=>0);
                foreach ($rawProduct->warehouses as $inventory){
                    $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->available));
                }
            }
            else{
                $tmpData = $this->get($rawProduct->sku);
                foreach ($rawProduct->warehouses as $inventory){
                    $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->available));
                }
            }
            $this->put($rawProduct->sku,$tmpData);
        }
    }


}

