<?php

namespace Sleefs\Helpers\ShipheroGQLApi;


use Sleefs\Helpers\curl\Curl;
use Sleefs\Helpers\GraphQL\GraphQLClient;

class ShipheroGQLApi {

	private $urlGql,$urlAuth,$accesToken,$refreshToken,$graphqlClient;

    public function __construct(GraphQLClient $graphqlClient, $urlGql = 'https://public-api.shiphero.com/graphql',$urlAuth = 'https://public-api.shiphero.com/auth',$accesToken,$refreshToken) {

        $this->urlGql = $urlGql;
        $this->urlAuth = $urlAuth;
        $this->accesToken = $accesToken;
        $this->refreshToken = $refreshToken;
        $this->graphqlClient = $graphqlClient;

    }


    public function getExtendedPO ($poId, $qtyLineItemsPerPage = 250, $afterForPagination = null)
    {
    	if ($poId == ''){
    		return false;
    	}

        $ctrlNextPage = 0;
        if ($afterForPagination == null)

            $postContent = array('query' => '{purchase_order(id:"'.$poId.'"){request_id,complexity,data{id,legacy_id,po_number,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,shipping_price,total_price,line_items(first:'.$qtyLineItemsPerPage.'){pageInfo{hasNextPage,startCursor,endCursor,}edges{node{id,price,po_id,account_id,warehouse_id,vendor_id,po_number,sku,barcode,note,quantity,quantity_received,quantity_rejected,product_name,fulfillment_status,vendor{id,name,email,account_id,account_number}}}}}}}');
        else 
            $postContent = array('query' => '{purchase_order(id:"'.$poId.'"){request_id,complexity,data{id,legacy_id,po_number,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,shipping_price,total_price,line_items(after:"'.$afterForPagination.'",first:'.$qtyLineItemsPerPage.'){pageInfo{hasNextPage,startCursor,endCursor}edges{node{id,price,legacy_id,po_id,account_id,warehouse_id,vendor_id,po_number,sku,barcode,note,quantity,quantity_received,quantity_rejected,product_name,fulfillment_status,vendor{id,name,email,account_id,account_number}}}}}}}');



    	$resp = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));
        if (isset($resp->data->purchase_order->data->line_items->pageInfo->hasNextPage))
        {
            if ($resp->data->purchase_order->data->line_items->pageInfo->hasNextPage == 1)
            { 
                $ctrlNextPage = $resp->data->purchase_order->data->line_items->pageInfo->hasNextPage;
                $afterForPagination = $resp->data->purchase_order->data->line_items->pageInfo->endCursor;

                while ($ctrlNextPage == 1)
                {
                    $postContent = array('query' => '{purchase_order(id:"'.$poId.'"){request_id,complexity,data{id,legacy_id,po_number,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,shipping_price,total_price,line_items(after:"'.$afterForPagination.'",first:'.$qtyLineItemsPerPage.'){pageInfo{hasNextPage,startCursor,endCursor}edges{node{id,price,legacy_id,po_id,account_id,warehouse_id,vendor_id,po_number,sku,barcode,note,quantity,quantity_received,quantity_rejected,product_name,fulfillment_status,vendor{id,name,email,account_id,account_number}}}}}}}');
                    $nextCall = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));
                    if (isset($nextCall->data->purchase_order->data->line_items->pageInfo->hasNextPage))
                    {
                        $ctrlNextPage = $nextCall->data->purchase_order->data->line_items->pageInfo->hasNextPage;
                        $afterForPagination = $nextCall->data->purchase_order->data->line_items->pageInfo->endCursor;
                        $resp->data->purchase_order->data->line_items->edges = array_merge($resp->data->purchase_order->data->line_items->edges,$nextCall->data->purchase_order->data->line_items->edges);
                    }
                    else
                    {
                        $ctrlNextPage = 0;
                    }
                }
                return $resp;
            }
            else
            {
                return $resp;
            }
        }
        else
        {
            return false;
        }
    }

    /**
    *
    * Recupera los productos existentes en el sistema shiphero
    *
    * @param    Array   $options (opcional) Arreglo asociativo (diccionario, tablahash) que puede integrar
    *                   los siguientes elementos:
    *                   
    *
    *                   $options['qtyProducts']: (opcional) Cantidad de productos por página
    *                   $options['afterForPagination']: (opcional) ID para "siguiente página"
    *                   $options['sku']: (opcional) SKU para realizar una búsqueda
    *                   $options['createdFrom']: (opcional) YYYY-MM-DD productos creados "desde"
    *                   $options['createdTo']: (opcional) YYYY-MM-DD productos creados "hasta"
    *                   $options['updatedFrom']: (opcional) YYYY-MM-DD productos actualizados "desde"
    *                   $options['updatedTo']: (opcional) YYYY-MM-DD productos actualizados "hasta"
    *                   $options['available']: (opcional) Boolean, indica si se requiere la definición
    *                                          de inventario con precisión "available" o no, para mayor
    *                                          información sobre la diferencia entre los campos
    *                                          "available" y "on_hand" verificar la documentación del API
    *                                          de shiphero.
    *
    * @return   stdClass $objectToRet Objeto generico con la siguiente estructura:
    *           
    *
                $objectToRet = stdClass Object
                (
                    [products] => stdClass Object
                        (
                            [results] => Array
                                (
                                    [0] => stdClass Object
                                        (
                                            [id] => UHJvZHVjdEluZm86MjU4MzYyNzAw
                                            [legacy_id] => 258362700
                                            [name] => Custom Head N Nek
                                            [sku] => 12321
                                            [barcode] => 321123
                                            [vendors] => Array
                                                (
                                                    [0] => stdClass Object
                                                        (
                                                            [vendor_id] => VmVuZG9yOjE5NTUw
                                                            [vendor_sku] => 
                                                        )

                                                    [1] => stdClass Object
                                                        (
                                                            [vendor_id] => VmVuZG9yOjMxOTg1MA==
                                                            [vendor_sku] => 
                                                        )

                                                )

                                            [warehouse_products] => Array
                                                (
                                                    [0] => stdClass Object
                                                        (
                                                            [id] => UHJvZHVjdDoyNTg3MTQ4NDI=
                                                            [legacy_id] => 258714842
                                                            [account_id] => QWNjb3VudDoxMTU3
                                                            [price] => 0.0000
                                                            [value] => 0.3800
                                                            [inventory_bin] =>  
                                                            [on_hand] => 0
                                                        )

                                                )

                                            [warehouses] => Array
                                                (
                                                    [0] => stdClass Object
                                                        (
                                                            [id] => UHJvZHVjdDoyNTg3MTQ4NDI=
                                                            [legacy_id] => 258714842
                                                            [account_id] => QWNjb3VudDoxMTU3
                                                            [price] => 0.0000
                                                            [value] => 0.3800
                                                            [inventory_bin] =>  
                                                            [on_hand] => 0
                                                        )

                                                )

                                        )

    *
    */

    public function getProducts($options = array())
    {
        $ctrlNextPage = 0;
        $paramsData = '';
        $objectToRet = new \stdClass();
        $objectToRet->products = new \stdClass();
        $objectToRet->products->results = array();
        $objectToRet->products->metaData = new \stdClass();

        if (isset($options['qtyProducts']) && $options['qtyProducts'] != ''){            
            $qtyProducts = 'first:'.$options['qtyProducts'];
            $paramsData .= $qtyProducts.",";
        }
        else
            $qtyProducts = '';

        if (isset($options['afterForPagination']) && $options['afterForPagination'] != ''){
            $afterForPagination = 'after:"'.$options['afterForPagination'].'"';
            $paramsData .= $afterForPagination.",";
        }
        else
            $afterForPagination = '';

        if ($paramsData != '')
            $paramsData = '('.$paramsData.')';

        //============================================================================
        $paramsProducts = '';

        if (isset($options['sku']) && $options['sku'] != ''){
            $sku = 'sku:"'.$options['sku'].'"';
            $paramsProducts .= $sku.',';
        }
        else
            $sku = '';

        if (isset($options['createdFrom']) && $options['createdFrom'] != ''){
            $createdFrom = 'created_from:"'.$options['createdFrom'].'"';
            $paramsProducts .= $createdFrom.',';
        }
        else
            $createdFrom = '';

        if (isset($options['createdTo']) && $options['createdTo'] != ''){
            $createdTo = 'created_to:"'.$options['createdTo'].'"';
            $paramsProducts .= $createdTo.',';
        }
        else
            $createdTo = '';

        if (isset($options['updatedFrom']) && $options['updatedFrom'] != ''){
            $updatedFrom = 'updated_from:"'.$options['updatedFrom'].'"';
            $paramsProducts .= $updatedFrom.',';
        }
        else
            $updatedFrom = '';

        if (isset($options['updatedTo']) && $options['updatedTo'] != ''){            
            $updatedTo = 'updated_to:"'.$options['updatedTo'].'"';
            $paramsProducts .= $updatedTo.',';
        }
        else
            $updatedTo = '';

        if ($paramsProducts != '')
            $paramsProducts = '('.$paramsProducts.')';

        //===================================================================

        if (isset($options['available']) && ($options['available'] == true || $options['available'] == 1))
        {
            $queryString = '{products'.$paramsProducts.'{complexity,request_id,data'.$paramsData.'{pageInfo{hasNextPage,startCursor,endCursor}edges{node{id,legacy_id,name,sku,barcode,vendors{vendor_id,vendor_sku}, warehouse_products{id,legacy_id,account_id,price,value,inventory_bin,on_hand,available}}}}}}';
        }
        else
        {
            $queryString = '{products'.$paramsProducts.'{complexity,request_id,data'.$paramsData.'{pageInfo{hasNextPage,startCursor,endCursor}edges{node{id,legacy_id,name,sku,barcode,vendors{vendor_id,vendor_sku}, warehouse_products{id,legacy_id,account_id,price,value,inventory_bin,on_hand}}}}}}';
        }

        $postContent = array('query' => $queryString);

        $resp = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));

        if (isset($resp->data->products->data->edges) && is_array($resp->data->products->data->edges))
        {
            $objectToRet->products->results = $resp->data->products->data->edges;
            $objectToRet->products->metaData = $resp->data->products->data->pageInfo;
            for($i=0;$i<count($objectToRet->products->results);$i++)
            {
                $objectToRet->products->results[$i] = $objectToRet->products->results[$i]->node;
                $objectToRet->products->results[$i]->warehouses = $objectToRet->products->results[$i]->warehouse_products;
            }
            return $objectToRet;
        }
        if (isset($resp->errors) && count($resp->errors)>0)
        {
            return $resp;
        }
        return false;
        //===================================================================
    }


    public function getProductsByWareHouse($wareHouseId,$options=null)
    {
        $productsQueryParam = '';
        if (isset($options['after']) && $options['after'] != '')
        {
            $productsQueryParam .=', after: "'.$options['after'].'"';
        }

        if (isset($options['qtyProducts']) && $options['qtyProducts'] != '')
        {
            $qtyProducts = $options['qtyProducts'];
        }
        else
        {
            $qtyProducts = 900;
        }

        $postContent = array('query' => '{warehouse_products(warehouse_id:"'.$wareHouseId.'"){complexity request_id data(first:'.$qtyProducts.''.$productsQueryParam.'){pageInfo{endCursor,startCursor hasNextPage}edges{node{id,legacy_id,account_id,price,value,inventory_bin,on_hand}}}}}');

        $resp = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));

        if (isset($resp->data->warehouse_products) && isset($resp->data->warehouse_products->data))
        {
            return $resp->data->warehouse_products->data;
        }
        else
        {
            return $resp;
        }
        
    }


    /**          
    *
    * This method creates a new product in shiphero platform
    * 
    * @param    mixed[] $productOptions         Arreglo asociativo con los valores que se deben suministrar para 
    *                                           crear un nuevo producto.
    * @return   mixed   $product                Arreglo asociativo con los datos del producto creado, o una estructura de error.          
    * 
    */

    public function createProduct($productOptions){

        //It starts the string:
        $createOptionsString = 'name: "'.$productOptions['name'].'" sku:"'.$productOptions['sku'].'" ';


        if (isset($productOptions['price']))
            $createOptionsString .= 'price: "'.$productOptions['price'].'" ';

        if (isset($productOptions['warehouse_products'])){
            $createOptionsString .= 'warehouse_products: {';

            if (isset($productOptions['warehouse_products']['warehouse_id']))
                $createOptionsString .= 'warehouse_id: "'.$productOptions['warehouse_products']['warehouse_id'].'" ';

            if (isset($productOptions['warehouse_products']['on_hand']))
                $createOptionsString .= 'on_hand: '.$productOptions['warehouse_products']['on_hand'].' ';

            if (isset($productOptions['warehouse_products']['custom']))
                $createOptionsString .= 'custom: "'.$productOptions['warehouse_products']['custom'].'" ';

            $createOptionsString .= '} ';
        }

        if (isset($productOptions['value']))
            $createOptionsString .= 'value: "'.$productOptions['value'].'" ';

        if (isset($productOptions['country_of_manufacture']))
            $createOptionsString .= 'country_of_manufacture: "'.$productOptions['country_of_manufacture'].'" ';

        if (isset($productOptions['barcode']))
            $createOptionsString .= 'barcode: "'.$productOptions['barcode'].'" ';

        //Defines the payload of the mutation request
        $postContent = array('query' => 'mutation{product_create(data:{'.$createOptionsString.'}){request_id complexity product{id legacy_id name sku price value}}}');
        //It dispatch the HTTP Request
        $resp = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));
        return $resp;

    }

    public function deleteProduct($productSku){

        //Defines the payload of the mutation request
        $postContent = array('query' => 'mutation{product_delete(data:{sku:"'.$productSku.'"}){request_id complexity}}');
        //It dispatch the HTTP Request
        $resp = $this->graphqlClient->query($postContent,array("Authorization: Bearer ".$this->accesToken,"Content-type: application/json"));
        return $resp;
        
    }


    public function refreshAccessToken ()
    {
        $refreshTokenContent = array("refresh_token" => $this->refreshToken);
        $headers = array("Content-type: application/json");
        $httpPostResp = Curl::urlPost($this->urlAuth."/refresh", $refreshTokenContent, $headers);
        $httpPostResp = json_decode($httpPostResp);
        if (isset($httpPostResp->access_token))
        {
            $this->accesToken = $httpPostResp->access_token;
            $this->saveNewAccessToken($httpPostResp->access_token);
        }
        return $httpPostResp;
    }


    public function saveNewAccessToken($key)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                'SHIPHERO_ACCESSTOKEN='.env('SHIPHERO_ACCESSTOKEN'), 'SHIPHERO_ACCESSTOKEN='.$key, file_get_contents($path)
            ));
        }
    }


    public function getAccessToken()
    {
        return $this->accesToken;
    }

}