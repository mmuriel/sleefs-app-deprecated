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


    public function getAllBoardGroups($boardId,$options=null) {
        if ($options == null)
            $url = $this->url . "boards/{$boardId}/groups.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$boardId}/groups.json?".$options."&api_key=".$this->apiKey;
        //echo $url;
        $groups = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($groups);
    }


    public function addGroupToBoard($boardId,$data,$options=null) {
        if ($options == null)
            $url = $this->url . "boards/{$boardId}/groups.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$boardId}/groups.json?".$options."&api_key=".$this->apiKey;
        //echo $url;
        $headers = array('Content-Type: multipart/form-data');
        $contents = Curl::urlPost($url, $data, $headers);
        //$contents = Curl::urlPost($url, $data);
        return json_decode($contents);
    }


    public function delBoardGroup($boardId,$groupId,$options=null) {
        if ($options == null)
            $url = $this->url . "boards/{$boardId}/groups/{$groupId}.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$boardId}/groups/{$groupId}.json?".$options."&api_key=".$this->apiKey;
        //echo $url;
        $dataToDelete = array();
        $dataToDelete['board_id'] = $boardId;
        $dataToDelete['group_id'] = $groupId;
        $headers = array('Content-Type: multipart/form-data');
        $delResponse = Curl::urlDelete($url, $dataToDelete, $headers);
        //$contents = Curl::urlPost($url, $data);
        return json_decode($delResponse);
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

    public function getPulse($pulseId,$options=null){
        if ($options == null)
            $url = $this->url . "pulses/{$pulseId}.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "pulses/{$pulseId}.json?".$options."&api_key=".$this->apiKey;

        //echo $url;
        $pulse = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($pulse);
    }

    public function getFullPulse(\Sleefs\Models\Monday\Pulse $pulse,$boardId,$options=null){

        $qtyPerPage = 25;
        $page = 1;
        $fullPulse = '';
        do{
            $boardPulses = $this->getBoardPulses($boardId,'page='.$page.'&per_page='.$qtyPerPage);
            for ($i = 0; $i < count($boardPulses);$i++){
                if ($boardPulses[$i]->pulse->id == $pulse->idmonday || $boardPulses[$i]->pulse->name == $pulse->name){
                    $fullPulse = $boardPulses[$i];
                    break;  
                }
            }
            $page++;
        }while(count($boardPulses) == $qtyPerPage && $fullPulse=='');

        if ($fullPulse==''){
            return null;
        }
        else{
            return $fullPulse;
        }
        

    }

    public function createPulse($idBoard,$data,$options=null){
        if ($options == null)
            $url = $this->url . "boards/{$idBoard}/pulses.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$idBoard}/pulses.json?".$options."&api_key=".$this->apiKey;

        $headers = array('Content-Type: multipart/form-data');
        $contents = Curl::urlPost($url, $data, $headers);
        //$contents = Curl::urlPost($url, $data);
        return json_decode($contents);
    }

    public function updatePulse($idBoard,$idPulse,$idColumn,$columnType,$data,$options=null){
        if ($options == null)
            $url = $this->url . "boards/{$idBoard}/columns/{$idColumn}/{$columnType}.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "boards/{$idBoard}/columns/{$idColumn}/{$columnType}.json?".$options."&api_key=".$this->apiKey;

        $dataToPulse = array();
        $dataToPulse['board_id'] = $idBoard;
        $dataToPulse['column_id'] = $idColumn;
        $dataToPulse['pulse_id'] = $idPulse;
        switch($columnType){
            case 'text':
                $dataToPulse['text'] = $data['text'];
                break;
            case 'person':
                $dataToPulse['user_id'] = $data['user_id'];
                break;
            case 'status':
                $dataToPulse['color_index'] = $data['color_index'];
                break;
            case 'date':
                $dataToPulse['date_str'] = $data['date_str'];
                break;
            case 'numeric':
                $dataToPulse['value'] = $data['value'];
                break;
            case 'tags':
                $dataToPulse['tags'] = $data['tags'];
                break;
            case 'timeline':
                $dataToPulse['from'] = $data['from'];
                $dataToPulse['to'] = $data['to'];
                break;
        }
        //echo $url;
        $headers = array('Content-Type: multipart/form-data');
        $contents = Curl::urlPUT($url,$dataToPulse,$headers);
        //print_r($contents);
        return json_decode($contents);
    }


    public function deletePulse($idPulse,$options=null){
        if ($options == null)
            $url = $this->url . "pulses/{$idPulse}.json?api_key=".$this->apiKey;
        else
            $url = $this->url . "pulses/{idPulse}.json?".$options."&api_key=".$this->apiKey;

        $dataToPulse = array();
        $dataToPulse['id'] = $idPulse;
        //echo $url;
        $headers = array('Content-Type: multipart/form-data');
        $contents = Curl::urlDelete($url,$dataToPulse,$headers);
        //print_r($contents);
        return json_decode($contents);
    }
}

?>
