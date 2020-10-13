<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;


class ShipheroGraphQLApiTest extends TestCase {

	public $po,$item1,$item2;

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();

        $this->po = new PurchaseOrder();
        $this->po->po_id = 515;
        $this->po->po_number = '1710-05 Brett Stern Order';
        $this->po->po_date = '2017-10-30 00:00:00';
        $this->po->fulfillment_status = 'closed';
		$this->po->save();

		$this->item1 = new PurchaseOrderItem();
		$this->item1->idpo = $this->po->id;
		$this->item1->sku = 'SL-USA-BLK-CL-L';
		$this->item1->shid = '59dbc5830f969';
		$this->item1->quantity = 5;
		$this->item1->quantity_received = 5;
		$this->item1->name = 'USA America Flag / Black Compression Tights / Leggings L / Red/White/Blue/Black';
		$this->item1->idmd5 = md5('SL-USA-BLK-CL-L'.'-'.'515');
		$this->item1->save();

		$this->item2 = new PurchaseOrderItem();
		$this->item2->idpo = $this->po->id;
		$this->item2->sku = 'SL-USA-BLK-CL-XL';
		$this->item2->shid = '59dbc5830fa20';
		$this->item2->quantity = 3;
		$this->item2->quantity_received = 3;
		$this->item2->name = 'USA America Flag / Black Compression Tights / Leggings XL / Red/White/Blue/Black';
		$this->item2->idmd5 = md5('SL-USA-BLK-CL-XL'.'-'.'515');
		$this->item2->save();
    }
 
    public function testRefreshToken()
    {
    	$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
    	$shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

    	$oldAccessToken = env('SHIPHERO_ACCESSTOKEN');
    	$newAccessToken = '';

    	$resp = $shipHeroApi->refreshAccessToken();
    	$arrEnv = file(base_path('.env'));
    	$savedAccessTokenInEnvFile = '';
    	foreach ($arrEnv as $envRecord){
    		if (preg_match("/SHIPHERO_ACCESSTOKEN=([^\"\>\<]{1,2000})/",$envRecord,$matches))
    		{
    			$newAccessToken = trim($matches[1]);
    			break;
    		}
    	}

    	$this->assertEquals($shipHeroApi->getAccessToken(),$newAccessToken);
    	$this->assertFalse(($shipHeroApi->getAccessToken() == $oldAccessToken));
    	//print_r($resp->access_token);
    }


    public function testGetExtendedPO()
    {
    	$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
    	$shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

    	$resp = $shipHeroApi->getExtendedPO(584025,25);
    	$this->assertEquals(75,count($resp->data->purchase_order->data->line_items->edges));
    }


    public function testGetProduct()
    {
    	$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
    	$shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
    }

	/* Preparing the Test */

	public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';

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