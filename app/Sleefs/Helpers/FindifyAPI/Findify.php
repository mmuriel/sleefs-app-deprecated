<?php

namespace Sleefs\Helpers\FindifyAPI;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

use Sleefs\Helpers\curl\Curl;
use \Sleefs\Helpers\Misc\Response;

class Findify {

    private $url;
    private $token;

    public function __construct($apiUrl) {

        /*
        $this->url = "https://" . Base::getConfigApp()->params['shopifyws']['SHP_TOKEN'];
        $this->url .= ":" . Base::getConfigApp()->params['shopifyws']['SHP_PWD'];
        $this->url .= "@" . Base::getConfigApp()->params['shopifyws']['SHP_APIURLBASE'];
         * 
         */
        //$this->url = "https://" . $apiToken;
        //$this->url .= ":" .$apiPwd;
        //$this->url = "https://admin.findify.io/v1/";
        $this->url = $apiUrl;
    }

    public function login($usr,$pwd) {

        $responseObj = new \Sleefs\Helpers\Misc\Response();
        $url = $this->url . "accounts/login";
        $postContent = array('login' => $usr,'password'=>$pwd);
        $contents = Curl::urlPost($url,$postContent);
        $contents = json_decode($contents);
        if (isset($contents->token)){
            $this->token = $contents->token;
            $responseObj->value = true;
            $responseObj->notes = $contents;
        }
        else{
            $responseObj->value = false;
            $responseObj->notes = $contents;
        }
        return $responseObj;
        
    }

    public function getAllCollections($merchanId,$apiKeyId) {
        
        $url = $this->url . "merchants/".$merchanId."/smart-collections/".$apiKeyId;
        $headers = array("x-token: ".$this->token);
        $contents = Curl::urlGet($url,$headers);
        return json_decode($contents);
    }


    /**
    * Para actualizar una colección se debe pasar como parámetro
    * un objeto tipo stdClass que al convertirlo a un objeto json 
    * debería tener la siguiente estructura:
    *
    *   "id":"57",//campo obligatorio
    *   "slot": "/collections/green",//campo obligatorio
    *   "query": {
    *       "filters": [
    *               {
    *                   "values": [
    *                       {
    *                           "value": "Green"
    *                       },
    *                       {
    *                           "value": "Orange"
    *                       }
    *                   ],
    *                   "type": "text",
    *                   "name": "color",
    *                  "action": "include"
    *              },
    *               {
    *                   "action": "exclude",
    *                   "values": [
    *                       {
    *                           "value": "Visor"
    *                       }
    *                   ],
    *                   "type": "text",
    *                   "name": "tags"
    *               }
    *           ],
    *       "q": ""
    *   }
    *
    *
    */

    public function updateCollection(\stdClass $smartCollection,$merchanId,$apiKeyId){

        $url = $this->url . "merchants/".$merchanId."/smart-collections/".$apiKeyId."/".$smartCollection->id;
        $headers = array("x-token: ".$this->token);
        $data = array(
            "id" => $smartCollection->id,
            "slot" => $smartCollection->slot,
            "query" => $smartCollection->query,
        );
        //$contents = Curl::urlGet($url,$headers);
        //print_r($data);
        $response = Curl::urlPUT($url, $data, $headers);
        return json_decode($response);

    }


}

?>
