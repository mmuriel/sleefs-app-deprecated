<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Sleefs\Controllers;


use \Sleefs\Helpers\ShopifyAPI\Interfaces\IProductPublishValidator;
use \Sleefs\Helpers\Misc\Response;
use \PHPMailer\PHPMailer\PHPMailer;
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

	public function publishProduct  (\stdClass $shopifyPrdt, IProductPublishValidator $validator){

        $response = new Response();
        //1.    Determina si el producto está para ser públicado
        $validationResult = $validator->isProductReadyToPublish($shopifyPrdt);
        if ($validationResult->value == false){
            
            //1.1.  Envia el correo de error
            if ($validationResult->notes == 'No images'){
                $textEmail = "Product: ".$shopifyPrdt->title." (https://sleefs-2.myshopify.com/admin/products/".$shopifyPrdt->id.") doesn't have at least one related image.";
                $this->sendNoPhotoInProductMsg($textEmail);
            }


            

            //1.2.  Define el valor de respuesta
            $response->value = false;
            $response->notes = $validationResult->notes;
            return $response; 
        }

        //2.    Determina si el producto es NEW o RES

        //3.    Actualiza el producto en shopify
        //3.1.  Define la fecha para el campo $shopifyProduct->published_at
        //3.2.  Actualiza precio, que ahora será: $shopifyProduct->compare_at_price
        //3.3.  Agrega el tag (NEWxxxxx o RESxxxxx según sea el caso) al campo: $shopifyProduct->tags

        //4.    Si el producto es NEW, agrega el tag 
        //      a la colección "new" en findify

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
            $mail->AddAddress("mauricio.muriel@calitek.net", "Mauricio Muriel");
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
