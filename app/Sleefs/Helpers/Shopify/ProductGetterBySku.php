<?php
namespace Sleefs\Helpers\Shopify;
class ProductGetterBySku {
    /**
    *
    *   This method looks for the product associated to a certain SKU code
    *   @param string $sku Sku code.
    *   @param \Sleefs\Models\Shopify\Product $product The object to return to.
    *
    *   @return \Sleefs\Models\Shopify\Product $product an object type: \Sleefs\Models\Shopify\Product
    *
    */

    public function getProduct($sku,\Sleefs\Models\Shopify\Product $product){
        $productRaw =  \DB::table('products')
                    ->join('variants','products.id','=','variants.idproduct')
                    ->select('products.*')
                    ->where('variants.sku','=',$sku)
                    ->first();
        if ($productRaw){
            $product->id = $productRaw->id;
            $product->vendor = $productRaw->vendor;
            $product->product_type = $productRaw->product_type;
            $product->handle = $productRaw->handle;
            $product->created_at = $productRaw->created_at;
            $product->updated_at = $productRaw->updated_at;
            if (!preg_match("/^shpfy_/",$productRaw->idsp))
                $product->idsp = "shpfy_".$productRaw->idsp;
            else
                $product->idsp = $productRaw->idsp;
            $product->title = $productRaw->title;
            return $product;
        }
        else{
            return null;
        }
    }
}

