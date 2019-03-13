<?php
namespace Sleefs\Helpers\Shopify;

use \Sleefs\Helpers\Misc\Response;
use \Sleefs\Helpers\ShopifyAPI\Interfaces\IProductPublishValidator;

class ProductPublishValidatorByImage implements IProductPublishValidator  {

    /**
    *
    *   This method validates if the passed (as argument) object, has images attribute and it is not published yet
    *   @param stdClass $rawProduct, 
    *
    *   @return \Sleefs\Helpers\Misc\Response, the product is ready to be published or not to be published
    *
    */

    public function isProductReadyToPublish($rawProduct): \Sleefs\Helpers\Misc\Response {

        //1. Validates if it has images attributes and this attribute is not empty,
        //   in other words, if there is at least one image associated to the product
        $returnObj = new \Sleefs\Helpers\Misc\Response();
        if (isset($rawProduct->images) && count($rawProduct->images) > 0){

            if ($rawProduct->published_at != null){

                $publishedTime = strtotime($rawProduct->published_at);
                $nowTime = time();
                if ($nowTime >= $publishedTime){

                    $returnObj->value = false;
                    $returnObj->notes = 'Product already published';
                }
                else{

                    $returnObj->value = true;
                    $returnObj->notes = 'Product ready to be published';
                }

            }
            else{
                $returnObj->value = true;
                $returnObj->notes = 'Product ready to be published';
            }
        }
        else{
            $returnObj->value = false;
            $returnObj->notes = 'No images';
        }
        return $returnObj;
    }
}

