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

    public function addElementsFromShipheroApi($jsonRawProducts,$options = array()){

        //Valida si los productos definen el valor available u on_hand
        $inventoryValueAvailable = false;
        if (isset($options['available']) && ($options['available']==1 || $options['available']==true))
            $inventoryValueAvailable = true;

        foreach ($jsonRawProducts as $rawProduct){

            if ($rawProduct->sku != '' && is_array($rawProduct->warehouses))
            {

                if (! $this->get($rawProduct->sku)){
                    $tmpData = array('qty'=>0);
                    foreach ($rawProduct->warehouses as $inventory)
                    {
                        if ($inventoryValueAvailable == true)
                        {
                            $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->available));
                        }
                        else
                        {
                            $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->on_hand));
                        }
                    }       
                }
                else{
                    $tmpData = $this->get($rawProduct->sku);
                    foreach ($rawProduct->warehouses as $inventory)
                    {
                        if ($inventoryValueAvailable == true)
                            $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->available));
                        else
                            $tmpData['qty'] = ($tmpData['qty'] + (int)($inventory->on_hand));
                    }
                }
                $this->put($rawProduct->sku,$tmpData);
            }
        }
    }


}

