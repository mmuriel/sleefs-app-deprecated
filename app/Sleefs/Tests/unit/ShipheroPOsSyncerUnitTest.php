<?php

namespace Sleefs\Test\integration;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Models\Shiphero\Vendor;
use Sleefs\Helpers\Shiphero\ShipheroFulfillmentStatusSyncedDataChecker;
use Sleefs\Helpers\Shiphero\ShipheroToLocalPODataSyncer;

use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\GraphQL\GraphQLClient;


class ShipheroPOsSyncerUnitTest extends TestCase {

	public $vendors = array();
	private $gqlVendorsRequest = '';

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();

    }
 

	public function testALocalPOMustHaveSameStatusThanRemotePO(){
		
		$localPo = new PurchaseOrder();
		$localPo->id = '1723';
		$localPo->po_id = '2244';
		$localPo->po_id_token = 'UHVyY2hhc2VPcmRlcjo1ODczNTY=';
		$localPo->po_id_legacy = '587356';
		$localPo->po_number = '2010-04 USA Re Order';
		$localPo->po_date = '2020-10-26 00:00:00';
		$localPo->fulfillment_status = 'closed';
		$localPo->sh_cost = 3349.50;


		$remotePo = new \stdClass();
		$remotePo->id = 'UHVyY2hhc2VPcmRlcjo1ODczNTY=';
		$remotePo->legacy_id = '587356';
		$remotePo->po_number = '2010-04 USA Re Order';
		$remotePo->po_date = '2020-10-26T00:00:00+00:00';
		$remotePo->account_id = 'QWNjb3VudDoxMTU3';
		$remotePo->vendor_id = 'VmVuZG9yOjA=';
		$remotePo->created_at = '2020-10-07T15:53:39+00:00';
		$remotePo->fulfillment_status = 'pending';
		$remotePo->po_note = '';
		$remotePo->description= '';
		$remotePo->subtotal = '3349.5';
		$remotePo->shipping_price = '0';
		$remotePo->total_price = '3349.5';


		$syncedDataChecker = new ShipheroFulfillmentStatusSyncedDataChecker();
		$this->assertFalse($syncedDataChecker->validateSyncedData($localPo,$remotePo));


	}


	public function testIfALocalPODoesntHaveSameStatusThanRemotePoThenItMustSync(){

		//Datos 
		$remotePo = new \stdClass();
		$remotePo->id = 'UHVyY2hhc2VPcmRlcjo1ODczNTY=';
		$remotePo->legacy_id = '587356';
		$remotePo->po_number = '2010-04 USA Re Order';
		$remotePo->po_date = '2020-10-26T00:00:00+00:00';
		$remotePo->account_id = 'QWNjb3VudDoxMTU3';
		$remotePo->vendor_id = 'VmVuZG9yOjA=';
		$remotePo->created_at = '2020-10-07T15:53:39+00:00';
		$remotePo->fulfillment_status = 'closed';
		$remotePo->po_note = '';
		$remotePo->description= '';
		$remotePo->subtotal = '3349.5';
		$remotePo->shipping_price = '0';
		$remotePo->total_price = '3349.5';	
		$remotePo->line_items = new \stdClass();	
		$remotePo->line_items->pageInfo = new \stdClass();
		$remotePo->line_items->edges = [];

		$remotePo->line_items->edges[0] = new \stdClass();
		$remotePo->line_items->edges[0]->node = new \stdClass();
		$remotePo->line_items->edges[0]->node->id = "UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5MDEwMjQ9239";
		$remotePo->line_items->edges[0]->node->po_id = "587356";
		$remotePo->line_items->edges[0]->node->vendor_id = "VmVuZG9yOjY5NDQy";
		$remotePo->line_items->edges[0]->node->sku = "SL-SBUSA-AS-XL";
		$remotePo->line_items->edges[0]->node->quantity = 25;
		$remotePo->line_items->edges[0]->node->quantity_received = 25;
		$remotePo->line_items->edges[0]->node->price = 0.50;

		$remotePo->line_items->edges[1] = new \stdClass();
		$remotePo->line_items->edges[1]->node = new \stdClass();
		$remotePo->line_items->edges[1]->node->id = "UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5MDExMTA=";
		$remotePo->line_items->edges[1]->node->po_id = "587356";
		$remotePo->line_items->edges[1]->node->vendor_id = "VmVuZG9yOjY5NDQy";
		$remotePo->line_items->edges[1]->node->sku = "SL-WASHSLP-WH";
		$remotePo->line_items->edges[1]->node->quantity = 20;
		$remotePo->line_items->edges[1]->node->quantity_received = 20;
		$remotePo->line_items->edges[1]->node->price = 1.1;
		//=========================================================

		$localPo = new PurchaseOrder();
		//$localPo->id = '1723';
		$localPo->po_id = '2244';
		$localPo->po_id_token = 'UHVyY2hhc2VPcmRlcjo1ODczNTY=';
		$localPo->po_id_legacy = '587356';
		$localPo->po_number = '2010-04 USA Re Order';
		$localPo->po_date = '2020-10-26 00:00:00';
		$localPo->fulfillment_status = 'pending';
		$localPo->sh_cost = 3349.50;
		$localPo->save();

		
		$poItem1 = new PurchaseOrderItem();
		$poItem1->idpo = $localPo->id;
		$poItem1->sku = "SL-SBUSA-AS-XL";
		$poItem1->name = "Softball USA Arm Sleeve";
		$poItem1->shid = "UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5MDEwMjQ9239";
		$poItem1->quantity = 25;
		$poItem1->quantity_received = 0;
		$poItem1->qty_pending = 25;
		$poItem1->price = 1.1;
		$poItem1->idmd5 = md5('SL-SBUSA-AS-XL'.'-'.$localPo->po_id);
		$poItem1->product_type = "Sleefs";

		$poItem1->save();
		
		$poItem2 = new PurchaseOrderItem();
		$poItem2->idpo = $localPo->id;
		$poItem2->sku = "SL-WASHSLP-WH";
		$poItem2->name = "War Shark SL Pattern Headband";
		$poItem2->shid = "UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5MDExMTA=";
		$poItem2->quantity = 20;
		$poItem2->quantity_received = 20;
		$poItem2->qty_pending = 0;
		$poItem2->price = 1.1;
		$poItem2->idmd5 = md5('SL-WASHSLP-WH'.'-'.$localPo->po_id);
		$poItem2->save();
		

		//Testing
		$syncedDataChecker = new ShipheroFulfillmentStatusSyncedDataChecker();
		$shipheroToLocalPODataSyncer = new ShipheroToLocalPODataSyncer();
		if (!$syncedDataChecker->validateSyncedData($localPo,$remotePo)){
			//NO son iguales
			$this->assertNotEquals($remotePo->line_items->edges[0]->node->quantity_received,$localPo->items[0]->quantity_received);
			list($error,$localPo) = $shipheroToLocalPODataSyncer->syncData($localPo,$remotePo);
			$this->assertFalse($error);
			$this->assertEquals($remotePo->line_items->edges[0]->node->quantity_received,$localPo->items[0]->quantity_received);
		}



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
    }

}