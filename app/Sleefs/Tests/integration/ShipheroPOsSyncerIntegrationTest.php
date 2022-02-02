<?php

namespace Sleefs\Test\integration;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\Shiphero\ShipheroFulfillmentStatusSyncedDataChecker;
use Sleefs\Helpers\Shiphero\ShipheroToLocalPODataSyncer;

use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\GraphQL\GraphQLClient;


class ShipheroPOsSyncerIntegrationTest extends TestCase {

	private $remotePo = '';
    private $pos = array();
    private $items = array();

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }
 

	public function testALocalPOMustHaveSameStatusThanRemotePO(){
		
        
        //print_r($this->gqlPoRequest);

        //Gets the (equivalent) local PO
        $localPo = PurchaseOrder::whereRaw("po_id = '1446'")->first();
		//print_r($localPo);

        //1. It verifies if the remote PO data and the local PO data share same fullfilment status
        $syncedDataChecker = new ShipheroFulfillmentStatusSyncedDataChecker();
        $this->assertFalse($syncedDataChecker->validateSyncedData($localPo,$this->remotePo));//It must be false, it means local PO data and remote PO data isn't the same.
	}


	public function testIfALocalPODoesntHaveSameStatusThanRemotePoItMustSync(){

        //Gets the (equivalent) local PO
        $localPo = PurchaseOrder::whereRaw("po_id = '1446'")->first();

        //1. It verifies that data is de-synced before syncing process
        //echo "\nRemote Fulfillment status: ".$this->remotePo->fulfillment_status."; Local Fulfillment status: ".$localPo->fulfillment_status."\n";
        $this->assertNotEquals($this->remotePo->fulfillment_status,$localPo->fulfillment_status);
        $this->assertNotEquals($this->remotePo->line_items->edges[0]->node->quantity_received,$localPo->items[0]->quantity_received);

        //2. It synces data between local PO data and remote PO data.
        $shipheroToLocalPODataSyncer = new ShipheroToLocalPODataSyncer();
        list($error,$localPo) = $shipheroToLocalPODataSyncer->syncData($localPo,$this->remotePo);
        $this->assertFalse($error);//Syncing musn't fail.

        //3. Now data must be synced!.
        $this->assertEquals($this->remotePo->fulfillment_status,$localPo->fulfillment_status);
        $this->assertEquals($this->remotePo->line_items->edges[0]->node->quantity_received,$localPo->items[0]->quantity_received);

        //4. It must be synced, even in persistent data.
        unset($localPo);
        $localPo = PurchaseOrder::whereRaw("po_id = '1446'")->first();
        $this->assertEquals('closed',$localPo->fulfillment_status);
	}


    public function testPuttingAllTogether(){

        

    }


	/* Preparing the Test */

	public function createApplication()
    {
        $app = require __DIR__.'/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

     /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     */
    private function prepareForTests()
    {

     	\Artisan::call('migrate');
        array_push($this->pos, new PurchaseOrder());
        $this->pos[0]->po_id = 1446;
        $this->pos[0]->po_id_legacy = 336860;
        $this->pos[0]->po_number = '1904-25 remake elite shorts';
        $this->pos[0]->po_date = '2019-04-22 00:00:00';
        $this->pos[0]->fulfillment_status = 'pending';
        $this->pos[0]->save();

        array_push($this->items,new PurchaseOrderItem());
        $this->items[0]->idpo = $this->pos[0]->id;
        $this->items[0]->shid = 'UHVyY2hhc2VPcmRlckxpbmVJdGVtOjQ2MTYxMTU=';
        $this->items[0]->sku = '123';
        $this->items[0]->quantity = 27;
        $this->items[0]->quantity_received = 15;
        $this->items[0]->qty_pending = 12;
        $this->items[0]->name = 'Custom Shorts';
        $this->items[0]->idmd5 = md5('123'.'-'.'1446');
        $this->items[0]->save();

        array_push($this->items,new PurchaseOrderItem());
        $this->items[1]->idpo = $this->pos[0]->id;
        $this->items[1]->shid = 'UHVyY2hhc2VPcmRlckxpbmVJdGVtOjQ2MTg5OTY=';
        $this->items[1]->sku = 'CUSTOM2-CL';
        $this->items[1]->quantity = 8;
        $this->items[1]->quantity_received = 3;
        $this->items[1]->qty_pending = 5;
        $this->items[1]->name = 'Custom Compression Tights (2nd design)';
        $this->items[1]->idmd5 = md5('CUSTOM2-CL'.'-'.'1446');
        $this->items[1]->save();


        array_push($this->items,new PurchaseOrderItem());
        $this->items[2]->idpo = $this->pos[0]->id;
        $this->items[2]->shid = 'UHVyY2hhc2VPcmRlckxpbmVJdGVtOjQ2MjAzMDI=';
        $this->items[2]->sku = 'CUSTOM-JS';
        $this->items[2]->quantity = 6;
        $this->items[2]->quantity_received = 4;
        $this->items[2]->qty_pending = 2;
        $this->items[2]->name = 'Custom Jersey';
        $this->items[2]->idmd5 = md5('CUSTOM-JS'.'-'.'1446');
        $this->items[2]->save();

        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql',array("Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN')));
        $gqlQuery = array("query" => '{purchase_order(id:"'.$this->pos[0]->po_id_legacy.'"){data{id,po_number,legacy_id,po_date,account_id,vendor_id,created_at,fulfillment_status,po_note,description,subtotal,total_price,images,vendor_id,line_items {pageInfo{startCursor,endCursor},edges{node{id,po_id,vendor_id,sku,quantity,quantity_received,quantity_rejected,price,product{product{name,tags,}}}}}}}}');
        $gqlPoRequest = $gqlClient->query($gqlQuery,array("Content-type: application/json"));
        
        //Gets the remote PO Data
        $this->remotePo = $gqlPoRequest->data->purchase_order->data;
    }

}