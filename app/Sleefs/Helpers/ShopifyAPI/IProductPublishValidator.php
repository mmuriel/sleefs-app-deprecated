<?php
namespace Sleefs\Helpers\ShopifyAPI;
interface IProductPublishValidator  {
    /**
    *
    *   This method looks for the product associated to a certain SKU code
    *   @param stdClass $rawProduct, 
    *
    *   @return \Sleefs\Helpers\Misc\Response, the product is ready to be published or to not be published
    *
    */

    public function isProductReadyToPublish(\stdClass $rawProduct): \Sleefs\Helpers\Misc\Response;
}

