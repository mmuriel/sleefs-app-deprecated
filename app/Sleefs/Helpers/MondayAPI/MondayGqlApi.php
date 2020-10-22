<?php

namespace Sleefs\Helpers\MondayApi;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

use Sleefs\Helpers\curl\Curl;
use Sleefs\Helpers\GraphQL\GraphQLClient;

class MondayGqlApi {

    private $gqlClient;

    public function __construct(GraphQLClient $gqlClient) {
        $this->gqlClient = $gqlClient;
    }

    public function getBoard($idBoard,$queryObject = null) {

        $postContent = array();
        if ($queryObject == null)
        {
            $postContent = array('query' => '{complexity{after before}boards(ids:'.$idBoard.'){id,name,board_kind,description,state,groups{color,id,position,title}columns{title,id,archived,type,width}}}');
        }
        else
        {
            if (is_array($queryObject))
                $postContent = $queryObject;
            else
                $postContent = array('query' => $queryObject);
        }


        $contents = $this->gqlClient->query($postContent);
        if (isset($contents->data->boards[0]) && isset($contents->data->boards[0]) && $contents->data->boards[0]->id == $idBoard)
            return $contents->data->boards[0];
        else
            return $contents;
    }

    public function getAllBoards($options=null) {
        if ($options == null)
            $options = '';
        else
            $options = '('.$options.')';

        //echo $url;
        $postContent = array('query' => '{complexity{after,before},boards'.$options.'{id,name,board_kind,description,state,groups{color,id,position,title}columns{title,id,archived,type,width}}}');
        

        //print_r($postContent);
        $contents = $this->gqlClient->query($postContent);

        return $contents;
    }

    public function getAllBoardGroups($boardId,$options=null) {
        
        $postContent = array('query'=>'{complexity{after,before}boards(ids:['.$boardId.']){id,name,groups{color,id,position,title}}}');
        //echo $url;
        $contents = $this->gqlClient->query($postContent);
        if (isset($contents->data->boards) && isset($contents->data->boards[0]->groups))
            return $contents->data->boards[0]->groups;
        else
            if (isset($contents->error) && $contents->error == true)
                return $contents;
            else
                return json_decode('{"error":true,"message":"error desconocido"}');
        //print_r($contents);
    }

    public function addGroupToBoard($boardId,$data,$options=null) {

        $postContent = array('query'=>'mutation{create_group(board_id:'.$boardId.',group_name:"'.$data['group_name'].'"){id}}');
        $contents = $this->gqlClient->query($postContent);
        return $contents;
    }

    public function delBoardGroup($boardId,$groupId,$options=null) {
        $postContent = array('query'=>'mutation{delete_group(board_id:'.$boardId.',group_id:"'.$groupId.'"){id}}');
        $contents = $this->gqlClient->query($postContent);
        return $contents;
    }

    /**
    * Recupera los pulsos (items) de un tablero.
    *
    * Este método recupera de un tablero los pulsos relacionados, en la cantidad
    * que se indiquen en el parámetro $option que deberá tener un string de la forma:
    * "(limit: xxx, page: yyy)" donde xxx y yyy son números enteros que indican
    * las condiciones de la "paginación" (en que página y cantidad de elementos por
    * petición).
    *
    * @param    integer $idBoard El id númerico del board en monday.com
    * @param    string $options (opcional) cadena de caracteres para la paginación
    * @return   array $content Un arreglo de objetos del tipo stdClass, con la siguiente 
    *           estructura:
    *           
    *           items [
    *           stdClass Object
                    (
                        [id] => 801976673
                        [name] => P120181252
                        [state] => active
                        [group] => stdClass Object
                            (
                                [id] => po_october_2020
                            )
    
                        [column_values] => Array
                            (
                                [0] => stdClass Object
                                    (
                                        [title] => Title
                                        [type] => text
                                        [text] => 
                                        [id] => title6
                                        [value] => 
                                    )
    
                                [1] => stdClass Object
                                    (
                                        [title] => Vendor
                                        [type] => text
                                        [text] => 
                                        [id] => vendor2
                                        [value] => 
                                    )

                                [2] => stdClass Object
                                    (
                                        [title] => Created Date
                                        [type] => date
                                        [text] => 
                                        [id] => created_date8
                                        [value] => 
                                    )

                                [3] => stdClass Object
                                    (
                                        [title] => Expected Date
                                        [type] => date
                                        [text] => 
                                        [id] => expected_date3
                                        [value] => 
                                    )

                                [4] => stdClass Object
                                    (
                                        [title] => Pay
                                        [type] => color
                                        [text] => Pending
                                        [id] => pay
                                        [value] => 
                                    )

                                [5] => stdClass Object
                                    (
                                        [title] => Received
                                        [type] => color
                                        [text] => 
                                        [id] => received
                                        [value] => 
                                    )

                                [6] => stdClass Object
                                    (
                                        [title] => Total Cost
                                        [type] => numeric
                                        [text] => 
                                        [id] => total_cost0
                                        [value] => 
                                    )

                            )
                    )

    *           ]
    *
    */

    public function getBoardPulses($idBoard,$options=null){
        if ($options == null){
            $page = 1;
            $limit = 1000;
            $options = '(limit:'.$limit.', page:'.$page.')';
        }
        $postContent = array('query' => '{complexity{after before}boards(ids:['.$idBoard.']){id,name,board_kind,description,state,items '.$options.'{id,name,state,group{id},column_values{title,type,text,id,value}}}}');
        $contents = $this->gqlClient->query($postContent); 
        
        if (isset($contents->data->boards[0]) && isset($contents->data->boards[0]->items))
            return $contents->data->boards[0]->items;
        else
            return $contents;
    }

    public function getPulse($pulseId,$options=null){
        //echo $url;
        $postContent = array('query' => '{complexity{before,after},items(ids:['.$pulseId.']){id,board{id,name},group{id,title},name,state,column_values{id text title type value}}}');
        
        $contents = $this->gqlClient->query($postContent);
        if (isset($contents->data->items) && isset($contents->data->items[0]) && $contents->data->items[0]->id == $pulseId)
        {
            return $contents->data->items[0];
        }
        else
            return json_decode('{"error":true,"message":"Pulse not found"}');
    }

    /**
    * Recupera un pulso (item) de un tablero con datos de un Pulse (local).
    *
    * Este método recupera un pulso (item) de un tablero a partir de los datos de un
    * objeto tipo Sleefs\Models\Monday\Pulse, particularmente, aplica la búsqueda a
    * partir de los campos Pulse.idmonday y Pulse.name
    *
    * @param    Sleefs\Models\Monday\Pulse $pulse El objeto pulse (local) para realizar
    *           la búsqueda.
    * @param    array $options (opcional)
    *
    * @return   stdClass $fullpulse Un objeto del tipo stdClass, con la siguiente 
    *           estructura:
    *           
                stdClass Object
                    (
                        [id] => 801976673
                        [name] => P120181252
                        [state] => active
                        [group] => stdClass Object
                            (
                                [id] => po_october_2020
                            )

                        [column_values] => Array
                            (
                                [0] => stdClass Object
                                    (
                                        [title] => Title
                                        [type] => text
                                        [text] => 
                                        [id] => title6
                                        [value] => 
                                    )

                                [1] => stdClass Object
                                    (
                                        [title] => Vendor
                                        [type] => text
                                        [text] => 
                                        [id] => vendor2
                                        [value] => 
                                    )

                                [2] => stdClass Object
                                    (
                                        [title] => Created Date
                                        [type] => date
                                        [text] => 
                                        [id] => created_date8
                                        [value] => 
                                    )

                                [3] => stdClass Object
                                    (
                                        [title] => Expected Date
                                        [type] => date
                                        [text] => 
                                        [id] => expected_date3
                                        [value] => 
                                    )

                                [4] => stdClass Object
                                    (
                                        [title] => Pay
                                        [type] => color
                                        [text] => Pending
                                        [id] => pay
                                        [value] => 
                                    )

                                [5] => stdClass Object
                                    (
                                        [title] => Received
                                        [type] => color
                                        [text] => 
                                        [id] => received
                                        [value] => 
                                    )

                                [6] => stdClass Object
                                    (
                                        [title] => Total Cost
                                        [type] => numeric
                                        [text] => 
                                        [id] => total_cost0
                                        [value] => 
                                    )

                            )
                    )
    *
    */
    public function getFullPulse(\Sleefs\Models\Monday\Pulse $pulse,$boardId,$options=null){

        $qtyPerPage = 25;
        $page = 1;
        $fullPulse = '';
        do{
            $boardPulses = $this->getBoardPulses($boardId,' (limit:'.$qtyPerPage.',page:'.$page.') ');
            //print_r($boardPulses);
            for ($i = 0; $i < count($boardPulses);$i++){
                if ($boardPulses[$i]->id == $pulse->idmonday || $boardPulses[$i]->name == $pulse->name){
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

    /**
    * Crea un nuevo pulse en un tablero (board) en monday.com
    *
    * Crea un nuevo pulse en un tablero (board) que se pasa como parámetro $idBoard, y se alimenta
    * con los datos que se pasan en el parámetro $data, que es un arreglo para dar flexibilidad
    * a los datos para la generación del pulse.
    *
    * @param    integer $idBoard ID del board en la plataforma de monday.com
    * @param    array $data Arreglo asociativo (diccionario) con los datos del pulse a generar
    *           los datos incluidos en este arreglo son los siguientes:
    *
    *           $data['group_id']       string ID del group en donde se registrará el pulse
    *           $data['item_name']      string nombre del pulse
    *           $data['column_values']  string (opcional) Una cadena de caracteres en formato json
    *                                   con la siguiente estructura y datos, SE DEBEN ESCAPAR LAS COMILLAS
    *                                   DOBLES CON \:
    *
    *                                    {
    *                                        "title6": "MMA - Titulo70",
    *                                        "vendor2": "People Sports",
    *                                        "created_date8": "2020-10-16 23:04:21",
    *                                        "expected_date3": "2020-10-26 10:00:00",
    *                                        "pay": "Pending",
    *                                        "received": "2",
    *                                        "total_cost0": "1200"
    *                                    }
    * @param    array $options (opcional) valores para pasar en el header de la petición http
    * 
    * @return   stdClass $contents un objeto de la clase genérica stdClass con la siguiente estructura:
    *
    *           {
    *              "data": {
    *                "create_item": {
    *                  "id": "803308072",
    *                  "name": "P120181270"
    *                }
    *            }
    *
    */

    public function createPulse($idBoard,$data,$options=null){

        $groupIdParam = '';
        if (isset($data['group_id']))
        {

            $groupIdParam = ',group_id:"'.$data['group_id'].'"';

        }

        if (isset($data['column_values']))
        {
            $jsonedColumnValues = json_encode($data['column_values']);
            $jsonedColumnValues = preg_replace("/([\x{201c}\x{201d}\x{0022}])/u",'\"',$jsonedColumnValues);
            $query = 'mutation{create_item(board_id:'.$idBoard.''.$groupIdParam.',item_name:"'.$data['item_name'].'",column_values:"'.$jsonedColumnValues.'"){id,name}}';
        }
        else
        {
            $query = 'mutation{create_item(board_id:'.$idBoard.''.$groupIdParam.',item_name:"'.$data['item_name'].'"){id,name}}';
        }
        $postContent = array('query' => $query);
        $contents = $this->gqlClient->query($postContent);
        //$contents = Curl::urlPost($url, $data);
        return $contents;
    }

    /**
    * Actualiza/Modifica el valor de una columna para un pulse 
    *
    * @param    integer $idBoard El ID en la plataforma monday.com del board donde 
    *           está el pulse que se quiere modificar
    * @param    integer $idPulse El ID en la plataforma monday.com del pulse que se
    *           requiere modificar
    * @param    string $idColumn El ID en la plataforma monday.com de la columna 
    *           que se requiere modificar
    * @param    string|integer|double $value El nuevo valor que se requiere ingresar en 
    *           en el campo.
    *
    * @return   stdClass $contents Objeto con la respuesta de la mutación, tiene la 
    *           siguiente estructura:
    *
                {
                  "data": {
                    "change_simple_column_value": {
                      "id": "801976969"
                    }
                  },
                  "account_id": 2316920
                }
    */
    public function updatePulse($idBoard,$idPulse,$idColumn,$value,$options=null){

        $query = 'mutation{change_simple_column_value(board_id:'.$idBoard.',item_id:'.$idPulse.',column_id:'.$idColumn.',value:"'.$value.'"){id}}';

        $postContent = array('query' => $query);
        $contents = $this->gqlClient->query($postContent);
        //$contents = Curl::urlPost($url, $data);
        return $contents;
    }

    public function deletePulse($idPulse,$options=null){
        $postContent = array('query' => 'mutation{delete_item(item_id: '.$idPulse.'){id}}');
        $contents = $this->gqlClient->query($postContent);
        //$contents = Curl::urlPost($url, $data);
        return $contents;
    }
}

?>
