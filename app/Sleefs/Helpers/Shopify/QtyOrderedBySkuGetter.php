<?php
namespace Sleefs\Helpers\Shopify;
class QtyOrderedBySkuGetter {
    /**
    *
    *   This method looks for the product associated to a certain SKU code
    *   @param string $sku Sku code.
    *
    *   @return integer
    *
    */
    public function getQtyOrdered($sku){
        $qtyRaw =  \DB::table('sh_purchaseorder_items')
                    ->select('sh_purchaseorder_items.quantity')
                    ->where('sh_purchaseorder_items.sku','=',$sku)
                    ->get();
        $qtyToRet = 0;
        if ($qtyRaw){
            foreach ($qtyRaw as $qty){
                $qtyToRet = $qtyToRet + $qty->quantity;
            }
        }
        return $qtyToRet;
    }
}

