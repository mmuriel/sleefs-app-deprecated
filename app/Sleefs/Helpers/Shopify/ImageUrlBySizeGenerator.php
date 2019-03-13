<?php
namespace Sleefs\Helpers\Shopify;
class ImageUrlBySizeGenerator {


    /**
    *
    *   This method looks for the product associated to a certain SKU code
    *   @param string $url, Base image url (without size parameters)
    *   @param integer $width, Expected width size of the image
    *   @param integer $height, Expected height size of the image
    *
    *   @return string $adjustedWidth
    *
    */

    public function createImgUrlWithSizeParam($url,$width = 150,$height = null){

        $adjustedUrl = $url;
        if ($height == null)
            $height = $width;

        $strToReplaceFor = '_'.$width.'x'.$height;

        $adjustedUrl = preg_replace("/\.(jpg|png|jpeg|gif)\?/",$strToReplaceFor."$0",$url);
        return $adjustedUrl;
        
    }
}

