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
    *
    *
    */

    public function getProducts($options)
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
        $postContent = array('query' => '{products'.$paramsProducts.'{complexity, request_id, data'.$paramsData.'{pageInfo{hasNextPage,startCursor,endCursor}edges{node{id,legacy_id,name,sku,barcode,vendors{vendor_id,vendor_sku}, warehouse_products{id,legacy_id,account_id,price,value,inventory_bin,available}}}}}}');

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


    /*

        

    */


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