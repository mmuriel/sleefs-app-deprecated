<?php
namespace Sleefs\Helpers\Shopify\Interfaces;


Interface IShopifyProductTagger{

    public function tagProduct(\stdClass $rawProduct,\Sleefs\Helpers\ShopifyAPI\Shopify $shopifyApi,\stdClass $options);

}

