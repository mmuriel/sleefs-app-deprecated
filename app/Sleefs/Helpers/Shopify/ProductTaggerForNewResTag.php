<?php
namespace Sleefs\Helpers\Shopify;

use Sleefs\Helpers\Shopify\Interfaces\IShopifyProductTagger;


Class  ProductTaggerForNewResTag implements IShopifyProductTagger{

    public function tagProduct(\stdClass $rawProduct,\Sleefs\Helpers\ShopifyAPI\Shopify $shopifyApi,\stdClass $options){

        //1. Determina el tag que debe aplicarse al producto
        $tag = $this->defineTag($rawProduct);

        //2. Limpiamos los posibles tags anteriores
        $tagLine = $rawProduct->tags;
        $tagLine = preg_replace("/(\ {0,1}NEW[0-9]{6,6}\,{0,1})/","",$tagLine);
        $tagLine = preg_replace("/(\ {0,1}RES[0-9]{6,6}\,{0,1})/","",$tagLine);
        $tagLine .= ', '.$tag;        

        $rawProduct->tags = $tagLine;
        //3. Envia la actualización de los tags a shopify

        $updateOperationResponse = $shopifyApi->updateProduct($rawProduct->id,array('product'=>array(
            'tags'=>$tagLine)));

        //print_r($updateOperationResponse);
        if (isset($updateOperationResponse->errors)){
            return false;
        }
        return $rawProduct;

    }


    public function defineTag (\stdClass $rawProduct){

        $windowTime = 60 * 60 * 24 * 60;//Time for 2 months
        $timeCreated = strtotime($rawProduct->created_at);
        
        $timeNow = strtotime("now");
        $alreadyTaggedNew = $this->isItAlreadyTaggedAsNEW($rawProduct);
        $alreadyTaggedAsRes = $this->isItAlreadyTaggedAsRES($rawProduct);
        $tagLine = $rawProduct->tags;

        //Limpia si tiene tags de marca tipo NEW o tipo RES del tag line
        $tagLine = preg_replace("/(\ {0,1}NEW[0-9]{6,6}\,{0,1})/","",$tagLine);
        $tagLine = preg_replace("/(\ {0,1}RES[0-9]{6,6}\,{0,1})/","",$tagLine);



        if ($timeCreated >= ($timeNow - $windowTime)){

            // El producto solo se ha registrado en el sistema en los últimos dos meses, es relativamente nuevo
            // debe registrarse como "NEW"
            if ($alreadyTaggedNew == true || $alreadyTaggedAsRes == true){

                //Se define como una etiqueta tipo RES
                $tag = 'RES'.date("mdy");

            }
            else{

                //Se define como una etiqueta tipo NEW
                $tag = 'NEW'.date("mdy");
            }

        }
        else{

            // El producto es "viejo" debe registrarse como RES (Re-Stocked)
            $tag = 'RES'.date("mdy");
        }
        return $tag;
    }


    private function isItAlreadyTaggedAsNEW(\stdClass $rawProduct){

        if (preg_match("/(NEW[0-9]{6,6}\,)/",$rawProduct->tags)){
            return true;
        }     
        else{
            return false;
        }

    }

    public function isItAlreadyTaggedAsRES(\stdClass $rawProduct){
        if (preg_match("/(RES[0-9]{6,6}\,)/",$rawProduct->tags)){
            return true;
        }     
        else{
            return false;
        }        
    }


}

