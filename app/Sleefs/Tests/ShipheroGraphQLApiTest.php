<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\Shiphero\ShipheroProductDeleter;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;


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

        /*
        
            It creates the Products and Variants to test creation and deletion
            on Shiphero's side

        */

        $this->prd1 = new Product();
        $this->prd1->idsp = 'shpfy_6640080715869';
        $this->prd1->title = 'MMA Test to Shirt - 100';
        $this->prd1->vendor = 'SLEEFS';
        $this->prd1->product_type = 'Test';
        $this->prd1->handle = 'mma-test-to-shirt-100';
        $this->prd1->delete_status = 1;
        $this->prd1->save();

        $this->variant1 = new Variant();
        $this->variant1->idsp = 'shpfy_395628303';
        $this->variant1->sku = 'MMA-PRD-TEST-TO-DEL-100';
        $this->variant1->title = 'Default Title';
        $this->variant1->idproduct = $this->prd1->id;
        $this->variant1->price = 0.0;
        $this->variant1->save();




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

        $resp = $shipHeroApi->getProducts();
        $this->assertEquals(100,count($resp->products->results)); 
    }


    public function testHandleNoCreditsError()
    {
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $resp = $shipHeroApi->getProducts(['qtyProducts'=>2000]);
        $this->assertRegExp("/^There are not enough credits/",$resp->errors[0]->message);
    }


    public function testGetProductWithAvailableField()
    {
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $resp = $shipHeroApi->getProducts(['available' => true,'qtyProducts' => 3]);
        $this->assertEquals(3,count($resp->products->results));

        $isAvailable = false;
        foreach ($resp->products->results as $prd)
        {
            if (isset($prd->warehouses[0]) && isset($prd->warehouses[0]->available))
            {
                $isAvailable = true;
                break;
            }
        }
        $this->assertTrue($isAvailable);
    }


    public function testGetProductsFromWarehouse()
    {
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $resp = $shipHeroApi->getProductsByWareHouse('V2FyZWhvdXNlOjE2ODQ=',["qtyProducts" => 5]);
        //print_r($resp);
        $this->assertEquals(1,$resp->pageInfo->hasNextPage);
        $this->assertEquals(5,count($resp->edges));
    }

    public function testGetProductsFromWarehouseError()
    {
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $resp = $shipHeroApi->getProductsByWareHouse('MMMV2FyZWhvdXNlOjE2ODQ=');
        $this->assertTrue(isset($resp->errors));
    }


    public function testCreateAndDeleteProduct(){
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
        $createProductData = array(
            'name'=>'MMA - SLEEFS SYNC APP TEST',
            'sku' => 'MMA-SLEEFS-SYNC-APP-TEST',
            'price'=>'100.23',
            'warehouse_products'=>array(
                'warehouse_id'=>'1684',
                'on_hand'=>0,
            ),
            'value'=> '39.00'
        );
        $createProductResponse = $shipHeroApi->createProduct($createProductData);
        if (isset($createProductResponse->data->product_create->product)){
            $this->assertEquals($createProductData['name'],$createProductResponse->data->product_create->product->name);
            //It deletes the product just created
            $deleteProductResponse = $shipHeroApi->deleteProduct($createProductData['sku']);
            $this->assertEquals(10,$deleteProductResponse->data->product_delete->complexity);
        }
        else{
            print_r($createProductResponse);
            $this->assertFalse(true);
        }
    }


    public function testDeleteAProductInShipheroByLocalProductID(){

        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        //It creates a new product in shiphero platform required to run this test.
        $createProductData = array(
            'name'=>'MMA Test to Shirt - 100',
            'sku' => 'MMA-PRD-TEST-TO-DEL-100',
            'price'=>'100.28',
            'warehouse_products'=>array(
                'warehouse_id'=>'1684',
                'on_hand'=>0,
            ),
            'value'=> '39.00'
        );
        
        //It looks for the product's sku to run the delete RPC in shiphero.
        $shphroProductDeleter = new ShipheroProductDeleter($shipHeroApi);

        //Assertion #1: Asserting to error=true, for an unknown product ID (1298 is not a valid product ID)
        $deleteActionResponse = $shphroProductDeleter->deleteProductInShiphero(1298);
        $this->assertTrue($deleteActionResponse->error);
        $this->assertEquals("No product found for ID: 1298",$deleteActionResponse->msg);

        //Assertion #2: Asserting to error=true, for not available product (identifyed by sku)
        //              in shiphero's side, it means, there is no product in shiphero
        //              associated to sku: MMA-PRD-TEST-TO-DEL-100

        $deleteActionResponse = $shphroProductDeleter->deleteProductInShiphero($this->prd1->id);
        $this->assertTrue($deleteActionResponse->variants[0]->error);//Yes, there must be an error
        $this->assertEquals("Not product with sku MMA-PRD-TEST-TO-DEL-100 exists",$deleteActionResponse->variants[0]->msg);

        //Assertion #3: It first creates, over the GraphQL API, a product (associated to sku: MMA-PRD-TEST-TO-DEL-100)
        //              in shiphero platform.   
        $createProductResponse = $shipHeroApi->createProduct($createProductData);
        $this->assertEquals('MMA-PRD-TEST-TO-DEL-100',$createProductResponse->data->product_create->product->sku);

        //Assertion #4: It deletes the product in shiphero platform, now, it should delete product smoothless.
        $deleteActionResponse = $shphroProductDeleter->deleteProductInShiphero($this->prd1->id);
        $this->assertFalse($deleteActionResponse->variants[0]->error);//Correct, no error deleting product by sku.
        $this->assertRegExp("/([a-f0-9]{24})\ \-\ ([0-9]{2,3})$/",$deleteActionResponse->variants[0]->msg);

    }


    public function testGetPoByPoNumber(){

        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $resp = $shipHeroApi->getExtendedPOCustomQuery('po_number:"1904-25 remake elite shorts"');
        //print_r($resp);
        $this->assertEquals(3,count($resp->data->purchase_order->data->line_items->edges));

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