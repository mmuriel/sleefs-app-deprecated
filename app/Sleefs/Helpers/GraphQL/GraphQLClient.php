<?php 
namespace Sleefs\Helpers\GraphQL;

use Sleefs\Helpers\curl\Curl;

class GraphQLClient{

	private $urlGql,$headers;

	public function __construct($urlGql,$headers = array()) {

        $this->urlGql = $urlGql;
        $this->headers = $headers;

    }


    public function query($queryString,$headers=array()){
    	$queryHeaders = array_merge($this->headers,$headers);
    	$httpResponse = Curl::urlPost($this->urlGql,$queryString,$queryHeaders);
        $queryResponse = json_decode($httpResponse);
        if ($queryResponse == null || $queryResponse == false)
        {
            $httpResponse = preg_replace("/[^a-zA-Z\ 0-9\.\,\<\>\/\{\}\-\:]/","",$httpResponse);
            return json_decode('{"error":true,"message":"'.$httpResponse.'"}');   
        }
        else
        {
            return $queryResponse; 
        }
    	
    }

}