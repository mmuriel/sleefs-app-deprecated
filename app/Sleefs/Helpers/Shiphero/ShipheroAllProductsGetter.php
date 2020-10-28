<?php

namespace Sleefs\Helpers\Shiphero;

use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\CustomLogger;

class ShipheroAllProductsGetter {

    public function getAllProducts($shipHeroParams,\Sleefs\Helpers\Shiphero\SkuRawCollection $collection){


        $gqlClient = new GraphQLClient($shipHeroParams['graphqlUrl']);
        $shipHeroApi = new ShipheroGQLApi($gqlClient,$shipHeroParams['graphqlUrl'],$shipHeroParams['authUrl'],env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $clogger = new CustomLogger("sleefs.inventoryreport.log");


        if (!isset($shipHeroParams['available']))
            $shipHeroParams['available'] = false;
    
        $options = array('qtyProducts'=>$shipHeroParams['qtyProducts'],'afterForPagination'=>'','available' => $shipHeroParams['available']);

        $tries = 0;
        $productsCpTmp = "";
        do {
            $tries++;
            $products = $shipHeroApi->getProducts($options);
            if (isset($products->errors) && preg_match("/^There are not enough credits to perfom the requested operation/",$products->errors[0]->message))
            {
                //echo "[Error] iteration {$tries}, ".$products->errors[0]->message."\n";
                $clogger->writeToLog ("iteration {$tries}, ".$products->errors[0]->message,"ERROR");

                preg_match("/\.\ In\ ([0-9]{1,3})\ seconds/",$products->errors[0]->message,$matches);
                $products = $productsCpTmp;
                if (!isset($products->products->metaData->hasNextPage))
                {
                    if ($products == "")
                        $products = new \stdClass();
                    $products->products = new \stdClass();
                    $products->products->metaData = new \stdClass();
                    $products->products->metaData->hasNextPage = 1;
                }
                sleep((int)($matches[1]+1));
            }
            else
            {
                //echo "[INFO] Adding new products to Raw Collection, iteration {$tries}, endcursor: {$products->products->metaData->endCursor}, \n";
                $clogger->writeToLog ("Adding new products to Raw Collection, iteration {$tries}, endcursor: {$products->products->metaData->endCursor}","INFO");

                $options['afterForPagination'] = $products->products->metaData->endCursor;
                $collection->addElementsFromShipheroApi($products->products->results);
                $productsCpTmp = $products;
            }
            //print_r($products->products->metaData);
        }
        while(($products->products->metaData->hasNextPage == 1) && $tries < $shipHeroParams['tries']);
        //while(false);
        //echo "\nTotal elementos en la coleccion: ".$collection->count()."\n";
        $totalPrds = $collection->count();
        $clogger->writeToLog ("Total products: {$totalPrds}","INFO");
        return $collection;
    }

}

