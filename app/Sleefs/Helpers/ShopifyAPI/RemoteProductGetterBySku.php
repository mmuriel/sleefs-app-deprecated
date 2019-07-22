<?php
namespace Sleefs\Helpers\ShopifyAPI;

use \Sleefs\Models\Shopify\Product;
use \Sleefs\Helpers\Shopify\ProductGetterBySku;

class RemoteProductGetterBySku {
    /**
    *
    *   This method looks for the product associated to a certain SKU code in shopify store
    *   @param string $sku, Sku code.
    *   @param \Sleefs\Helpers\ShopifyAPI\Shopify $shopifyApi, a shopify api object to make REST calls
    *
    *   @return \stdClass $product or null
    *
    */

    public function getRemoteProductBySku($sku,\Sleefs\Helpers\ShopifyAPI\Shopify $shopifyApi){

        $localProductGetter = new ProductGetterBySku();
        $localProduct = new Product();

        $localProduct = $localProductGetter->getProduct($sku,$localProduct);
        if ($localProduct){

            $options = 'handle='.$localProduct->handle;
            $remoteRaw = $shopifyApi->getAllProducts($options);
            if (count($remoteRaw->products) >= 1){
                $product = $remoteRaw->products[0];
                return $product;

            }else{
                return null;
            }
        }
        else{

            return null;

        }
    }
}

