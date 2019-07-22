<?php

namespace Sleefs\Helpers\MondayApi;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

use Sleefs\Helpers\curl\Curl;

class MondayApi {

    private $url;
    private $apiKey;

    public function __construct($apiUrl,$apiKey) {

        /*
        $this->url = "https://" . Base::getConfigApp()->params['shopifyws']['SHP_TOKEN'];
        $this->url .= ":" . Base::getConfigApp()->params['shopifyws']['SHP_PWD'];
        $this->url .= "@" . Base::getConfigApp()->params['shopifyws']['SHP_APIURLBASE'];
         * 
         */
        $this->url = "https://" . $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function getBoard($idBoard) {

        $url = $this->url . "boards/{$idBoard}.json"."?api_key=".$this->apiKey;
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getAllBoards($options=null) {
        if ($options == null)
            $url = $this->url . "boards.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards.json?".$options."&api_key=".$this->apiKey;

        //echo $url;
        $contents = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($contents);
    }

    public function getBoardPulses($idBoard,$options=null){
        if ($options == null)
            $url = $this->url . "boards/{$idBoard}/pulses.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$idBoard}/pulses.json?".$options."&api_key=".$this->apiKey;

        //echo $url;
        $contents = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($contents);
    }

    public function createPulse($idBoard,$options=null){
        if ($options == null)
            $url = $this->url . "boards/{$idBoard}/pulses.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$idBoard}/pulses.json?".$options."&api_key=".$this->apiKey;

        //echo $url;
        $contents = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($contents);
    }

    public function updatePulse($idBoard,$options=null){
        if ($options == null)
            $url = $this->url . "boards/{$idBoard}/pulses.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$idBoard}/pulses.json?".$options."&api_key=".$this->apiKey;

        //echo $url;
        $contents = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($contents);
    }
}

?>
