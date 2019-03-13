<?php

namespace Sleefs\Helpers\Shiphero;

use \Sleefs\Helpers\Shiphero\SkuRawCollection;
use \Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;

use \Sleefs\Helpers\Shopify\ProductGetterBySku;
use \Sleefs\Helpers\Shopify\QtyOrderedBySkuGetter;

use \Illuminate\Support\Collection;

use \Sleefs\Models\Shiphero\items;
use \Sleefs\Models\Shopify\Product;


use \Sleefs\Models\Shiphero\InventoryReport;
use \Sleefs\Models\Shiphero\InventoryReportItem;

class ShipheroDailyInventoryReport {


    /**
    * This method creates 
    *
    * @param mixed $shipHeroParams, an associative array with at leats next two params $shipHeroParams['apikey'] and
    * $shipHeroParams['qtyperpage']
    */

    public function createReport($shipHeroParams){

        
        $inMemoryProducts = new SkuRawCollection();
        $shipHeroProductsGetter = new ShipheroAllProductsGetter();
        
        $reportCollection = collect();
        $shopifyProductGetter = new ProductGetterBySku();
        //1. Recupera todos los productos
        $inMemoryProducts = $shipHeroProductsGetter->getAllProducts($shipHeroParams,$inMemoryProducts);


        //print_r($inMemoryProducts);
        //2. Recupera 
        // 2.1. El tipo de producto por cada sku
        // 2.2. La cantidad ordenada de productos por SKU (ordenes abiertas)

        $ctrlQty = 1;
        foreach ($inMemoryProducts as $key=>$item){
            $tmpProduct = new Product();
            $shopifyProductGetter = new ProductGetterBySku();
            $tmpProduct = $shopifyProductGetter->getProduct($key,$tmpProduct);

            if ($tmpProduct != null){
                //return $item;
                $item['product_type'] = $tmpProduct->product_type; //$reportCollection
                $item['inorder_qty'] = 0;
            }
            else{

                $item['product_type'] = 'n/a';
                $item['inorder_qty'] = 0;

            }
            $poItemsQty =  \DB::table('sh_purchaseorder_items')
                        ->leftJoin('sh_purchaseorders','sh_purchaseorder_items.idpo','=','sh_purchaseorders.id')
                        ->select('sh_purchaseorder_items.qty_pending')
                        ->whereRaw("(sh_purchaseorders.fulfillment_status != 'closed' and sh_purchaseorders.fulfillment_status != 'canceled') and sh_purchaseorder_items.sku='".$key."' ")
                        ->get();

            

            //echo $ctrlQty.". Procesando para ".$key."\n";
            $ctrlQty++;


            $totalInOrder = 0;
            if ($poItemsQty->count() > 0){
                //Si hay elementos ordenados
                foreach ($poItemsQty as $rawOrderItem){
                    $totalInOrder = $totalInOrder + ((int) $rawOrderItem->qty_pending);
                }

            }
            $item['inorder_qty'] = $totalInOrder;
            $inMemoryProducts[$key]=$item;

            //----------------------------------------------------------------------------------
            if ($item['product_type']!='n/a'){
                $reportCollectionItem = $reportCollection->get($item['product_type']);
                if ($reportCollectionItem){
                    $reportCollectionItem['qty'] = $reportCollectionItem['qty'] + $item['qty'];
                    $reportCollectionItem['inorder_qty'] = $reportCollectionItem['inorder_qty'] + $item['inorder_qty'];
                }
                else{
                    $reportCollectionItem = array(
                        'qty' => $item['qty'],
                        'inorder_qty' => $item['inorder_qty']
                    );
                }
                $reportCollection->put($item['product_type'],$reportCollectionItem);

            }
        }

        $inventoryReport = new InventoryReport();
        $inventoryReport->save();

        foreach ($reportCollection as $key=>$item){
            $reportItem = new InventoryReportItem();
            $reportItem->idreporte = $inventoryReport->id;
            $reportItem->label = $key;
            $reportItem->total_inventory = $item['qty'];
            $reportItem->total_on_order = $item['inorder_qty'];
            $reportItem->save();
        }

        return $inventoryReport;
    }

}

