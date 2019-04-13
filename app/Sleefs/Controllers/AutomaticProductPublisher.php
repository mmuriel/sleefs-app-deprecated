<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Sleefs\Controllers;


use \Sleefs\Helpers\ShopifyAPI\Interfaces\IProductPublishValidator;
use \Sleefs\Helpers\Shopify\Interfaces\IShopifyProductTagger;
use \Sleefs\Helpers\ShopifyAPI\Shopify;
use \Sleefs\Helpers\Misc\Response;
use \PHPMailer\PHPMailer\PHPMailer;
use \Sleefs\Helpers\FindifyAPI\Findify;
/**
 * Description of newPHPClass
 *
 * @author @maomuriel
 * mauricio.muriel@calitek.net
 */
class AutomaticProductPublisher {

    //put your code here

    /**
    * 
    * @param    (stdClass) $shopifyPrdt, This is a stdClass object representing a shopify store product, this object must have at least 
    *           next fields:
    *
    *           $shopifyPrdt->id (Shopify ID)
    *           $shopifyPrdt->handle (Shopify URL handle)
    *           $shopifyPrdt->published (null / product published date)
    *           $shopifyPrdt->images (Array of image urls, associated to product)
    *
    * @param    (\Sleefs\Helpers\ShopifyAPI\Interfaces\IProductPublishValidator) $validator, Validator by image object
    * 
    *
    */

	public function publishProduct  (\stdClass $shopifyPrdt, IProductPublishValidator $validator, Shopify $shopifyApi, IShopifyProductTagger $tagger, Findify $findifyApi){

        $response = new Response();
        //1.    Determina si el producto está para ser públicado
        $validationResult = $validator->isProductReadyToPublish($shopifyPrdt);
        if ($validationResult->value == false){
            
            //1.1.  Envia el correo de error
            if ($validationResult->notes == 'No images'){
                $textEmail = "Product: ".$shopifyPrdt->title." (https://".env('SHPFY_BASEURL')."/products/".$shopifyPrdt->id.") doesn't have at least one related image.";
                $this->sendNoPhotoInProductMsg($textEmail);
            }
            //1.2.  Define el valor de respuesta
            $response->value = false;
            $response->notes = $validationResult->notes;
            return $response; 
        }

        //2.    Determina si el producto es NEW o RES
        $tag = $tagger->defineTag($shopifyPrdt);
        //3.    Actualiza el producto en shopify
        //3.1.  Define la fecha para el campo $shopifyProduct->published_at
        $shopifyPrdt->published_at = date(DATE_ATOM);
        //3.2.  Actualiza precio, que ahora será: $shopifyProduct->compare_at_price
        for ($i=0;$i < count($shopifyPrdt->variants);$i++){

            $shopifyPrdt->variants[$i]->price = $shopifyPrdt->variants[$i]->compare_at_price;

        }
        //3.3.  Agrega el tag (NEWxxxxx o RESxxxxx según sea el caso) al campo: $shopifyProduct->tags
        $shopifyPrdt->tags = $shopifyPrdt->tags.", ".$tag;

        $shopifyResponseUpdate = $shopifyApi->updateProduct($shopifyPrdt->id,array(
            'product'=>array(
                'tags'=>$shopifyPrdt->tags,
                'published_at'=>$shopifyPrdt->published_at,
                'variants'=>$shopifyPrdt->variants,
            )
        ));
        //4.    Si el producto es NEW, agrega el tag 
        //      a la colección "new" en findify

        if (preg_match("/^NEW[0-9]{2,6}/",$tag)){

            //$loginResult = $findifyApi->login('admin@sleefs.com','Sleefs--5931');
            $loginResult = $findifyApi->login(env('FINDIFY_USR'),env('FINDIFY_PWD'));
            $collections = $findifyApi->getAllCollections(env('FINDIFY_MERCHANT_ID'),env('FINDIFY_API_KEY'));
            $newCollection = new \stdClass();
            foreach ($collections as $rawCollection){
                if (preg_match("/\/collections\/new$/",$rawCollection->slot)){
                     $newCollection = $rawCollection;
                     break;
                }
            }
            

            //======================================================================
            // Verifica si debe ingresar el nuevo tag como filtro
            // o si ya el filtro existe en la colección de findify
            //======================================================================
            
            $ctrlAddTag = true;
            foreach($newCollection->query->filters[0]->values as $filter){
                if (strtoupper($filter->value) == $tag){
                    $ctrlAddTag=false;
                    break;
                }
            }

            if ($ctrlAddTag){

                $newFilter = new \stdClass();
                $newFilter->value = $tag;
                array_push($newCollection->query->filters[0]->values,$newFilter);
                array_shift($newCollection->query->filters[0]->values);
                $respFindiCollUpdate = $findifyApi->updateCollection($newCollection,env('FINDIFY_MERCHANT_ID'),env('FINDIFY_API_KEY'));
                //echo "\nFindify Response\n";
                //print_r($respFindiCollUpdate);
            }
            else{

                //echo "No actualiza...";
            }
            

        }

        $response->value = true;
        $response->notes = "ok";
        return $response;

    }


    private function sendNoPhotoInProductMsg($textEmail){

        $response = new Response();
        try{
            $text             = "The product ".$textEmail." doesn't have a related image";
            $mail             = new PHPMailer();
            $mail->isSMTP();
            $mail->SMTPDebug  = 1; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth   = true; // authentication enabled
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION'); // secure transfer enabled REQUIRED for Gmail
            $mail->Host       = getenv('MAIL_HOST');
            $mail->Port       = getenv('MAIL_PORT'); // or 587
            $mail->IsHTML(true);
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SetFrom("mauricio.muriel@sientifica.com", 'Mauricio Muriel');
            $mail->Subject = "A product in store doesn't have a related image";
            $mail->Body    = $text;
            //$mail->AddAddress("mauricio.muriel@calitek.net", "Mauricio Muriel");
            $mail->AddAddress("jschuster@sleefs.com", "Jaime Schuster");
            $mail->Send();
            $response->value = true;
        }
        catch(\Exception $e){
            $response->value = false;
            $response->status = false;
            $response->notes = $mail->ErrorInfo;
            return $response;
        }

        return $response;
    }

}
