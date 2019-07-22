<?php

namespace Sleefs\Helpers\Shiphero;
use \mdeschermeier\shiphero\Shiphero;

class ShipheroAllProductsGetter {

    public function getAllProducts($shipHeroParams,\Sleefs\Helpers\Shiphero\SkuRawCollection $collection){

        Shiphero::setKey($shipHeroParams['apikey']);
        $page = 0;
        $tries = 0;
        do {
            $page++;
            $tries++;
            $options = array('page'=>$page, 'count'=>$shipHeroParams['qtyperpage']);
            $products = Shiphero::getProduct($options);
            //echo "Intento ".$tries."\n";
            $collection->addElementsFromShipheroApi($products->products->results);
        }
        while(is_array($products->products->results) && $tries < 50);
        return $collection;
    }

}

