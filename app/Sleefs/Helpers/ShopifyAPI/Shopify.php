<?php

namespace Sleefs\Helpers\ShopifyAPI;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

use Sleefs\Helpers\curl\Curl;

class Shopify {

    private $url;

    public function __construct($apiToken,$apiPwd,$apiUrl) {

        /*
        $this->url = "https://" . Base::getConfigApp()->params['shopifyws']['SHP_TOKEN'];
        $this->url .= ":" . Base::getConfigApp()->params['shopifyws']['SHP_PWD'];
        $this->url .= "@" . Base::getConfigApp()->params['shopifyws']['SHP_APIURLBASE'];
         * 
         */
        $this->url = "https://" . $apiToken;
        $this->url .= ":" .$apiPwd;
        $this->url .= "@" .$apiUrl;
    }

    public function getSingleOrder($id) {

        $url = $this->url . "orders/{$id}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getAllOrders($options=null) {
        if ($options == null)
            $url = $this->url . "orders.json";
        else
            $url = $this->url . "orders.json?".$options;

        //echo $url;
        $contents = Curl::urlGet($url);
        //print_r($contents);
        return json_decode($contents);
    }

    public function getAllProducts($options=null) {
        
        if ($options == null)
            $url = $this->url . "products.json";
        else
            $url = $this->url . "products.json?".$options;

        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getAllProductsByCollection($id) {

        $url = $this->url . "products.json?collection_id={$id}";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getCountProducts() {
        $url = $this->url . "products/count.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }


    public function getCountOrders($filter = null) {

        if ($filter == null)
            $url = $this->url . "products/count.json";
        else
            $url = $this->url . "products/count.json?".$filter;

        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }


    public function getSingleProduct($id) {

        $url = $this->url . "products/{$id}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getAllImagesProduct($id) {
        $url = $this->url . "products/{$id}/images.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getSingleImageProduct($id_product, $id_image) {
        $url = $this->url . "products/{$id_product}/images/{$id_image}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getSingleProductVariant($id_variant) {
        $url = $this->url . "/variants/{$id_variant}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getAllProductVariants($id_product) {
        $url = $this->url . "/products/{$id_product}/variants.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function getWebHook() {
        $url = $this->url . "webhooks.json";
        $contents = Curl::urlGet($url);
        return $contents;
    }

    public function setNoteOrder($id, $note) {
        $data['order'] = array('id' => $id, 'note' => $note);
        $url = $this->url . "orders/{$id}.json";
        $contents = Curl::urlPUT($url, $data);
        return ($contents);
    }


    public function updateProductInventory ($productId,$variants){

        $data= array();
        $data['product'] = array('id' => $productId, 'variants' => $variants);
        $url = $this->url . "products/{$productId}.json";
        $contents = Curl::urlPUT($url,$data);
        return ($contents);

    }

    public function getCustomer($id) {
        $url = $this->url . "/customers/{$id}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function createTransaction($id,$amount) {

        $data['transaction'] = array('amount' => $amount,"kind"=>"capture");
        $url = $this->url . "orders/{$id}/transactions.json";
        $contents = Curl::urlPost($url, $data);
        return ($contents);
    }

    public function getFulfillmentByOrder($order){

        $url = $this->url . "/orders/{$order}/fulfillments.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);

    }

    public function getOrderRiskLevel($order){

        $url = $this->url . "/orders/{$order}/risks.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);

    }

    public function setFulfillmentByOrder ($id,$tracking,$items){

        //$data['fulfillment'] = array('amount' => $amount,"kind"=>"capture");
        $data['fulfillment'] = array();
        $url = $this->url . "orders/{$id}/fulfillments.json";

        //Preparing data

        //Tracking numbers
        switch (count($tracking)){

            case 0:     $data['fulfillment']['tracking_number'] = null;
                        break;

            case 1:     $data['fulfillment']['tracking_number'] = $tracking[0];
                        break;

            default:    $data['fulfillment']['tracking_numbers'] = $tracking;
                        break;

        }

       
        //If there are multiple items
        if (count($items)>=1){

            $data['fulfillment']['line_items'] = array();
            foreach ($items as $item){

                $arrTmp = array("id" => $item);
                array_push($data['fulfillment']['line_items'],$arrTmp);

            }

        }

        $data['fulfillment']['notify_customer'] = true;
        //============================================================
        //Posting data
        //============================================================
        $contents = Curl::urlPost($url,$data);
        return ($contents);

    }


    public function setFulfillmentByOrderOnly ($idorder,$tracking,$notify=true){

        //$data['fulfillment'] = array('amount' => $amount,"kind"=>"capture");
        $data['fulfillment'] = array();
        $url = $this->url . "orders/{$idorder}/fulfillments.json";

        //Preparing data

        //Tracking numbers
        $data['fulfillment']['tracking_number'] = $tracking;
        //Notify user
        $data['fulfillment']['notify_customer'] = $notify;
        //============================================================
        //Posting data
        //============================================================
        $contents = Curl::urlPost($url,$data);
        return ($contents);

    }

    public function verifyWebHook($data, $hmac_header)
    {
      $calculated_hmac = base64_encode(hash_hmac('sha256', $data,SHP_SEC, true));
      return ($hmac_header == $calculated_hmac);
    }

}

?>
