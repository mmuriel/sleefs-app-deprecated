<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Models\Shiphero\Vendor;
use Sleefs\Helpers\Shiphero\ShipheroVendorsDiffChecker;

use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\GraphQL\GraphQLClient;


class ShipheroVendorsSyncerTest extends TestCase {

	public $vendors = array();
	private $gqlVendorsRequest = '';

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();

        array_push($this->vendors,new Vendor());
        $this->vendors[0]->idsp = "VmVuZG9yOjE2NDMwMA==";
        $this->vendors[0]->name = "Amy Xu";
        $this->vendors[0]->legacy_idsp = "164300";
        $this->vendors[0]->email = "154117005@qq.com";
        $this->vendors[0]->save();


        array_push($this->vendors,new Vendor());
        $this->vendors[1]->idsp = "VmVuZG9yOjE4Mzg1";
        $this->vendors[1]->name = "DX Sporting Goods";
        $this->vendors[1]->legacy_idsp = "18385";
        $this->vendors[1]->email = "xxx";
        $this->vendors[1]->save();

        array_push($this->vendors,new Vendor());
        $this->vendors[2]->idsp = "VmVuZG9yOjE2NDAzNA==";
        $this->vendors[2]->name = "Dongguan Yuanshan Webbing & Accessories Co";
        $this->vendors[2]->legacy_idsp = "164034";
        $this->vendors[2]->email = "dgty2000@163.com";
        $this->vendors[2]->save();

        array_push($this->vendors,new Vendor());
        $this->vendors[3]->idsp = "VmVuZG9yOjE0MDI3NQ==";
        $this->vendors[3]->name = "EliteTek Sports";
        $this->vendors[3]->legacy_idsp = "140275";
        $this->vendors[3]->email = "office@elitetek.com";
        $this->vendors[3]->save();

        array_push($this->vendors,new Vendor());
        $this->vendors[4]->idsp = "VmVuZG9yOjY5NDQy";
        $this->vendors[4]->name = "Good People Sports";
        $this->vendors[4]->legacy_idsp = "69442";
        $this->vendors[4]->email = "953440032@qq.com";
        $this->vendors[4]->save();

        array_push($this->vendors,new Vendor());
        $this->vendors[5]->idsp = "VmVuZG9yOjMzNzc0OA==";
        $this->vendors[5]->name = "FUJIAN QUANZHOU HV";
        $this->vendors[5]->legacy_idsp = "156677";
        $this->vendors[5]->email = "ruby@hvtex.com";
        $this->vendors[5]->save();

        //Get data from Shiphero GQL API
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql',array("Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN')));
        $gqlQuery = array("query" => '{vendors{data(first:100){edges{node{name,legacy_id,id,email,account_number}}}}}');
        $this->gqlVendorsRequest = $gqlClient->query($gqlQuery,array("Content-type: application/json"));

    }
 

	public function testInmemoryDatabaseAddedRecords(){
		/* Testing saved items to database */
		$this->assertDatabaseHas('sh_vendors',['idsp' => 'VmVuZG9yOjE2NDAzNA==','name' => 'Dongguan Yuanshan Webbing & Accessories Co']);
	}


	public function testCheckDiffBetweenMockedApiAndLocalStorage(){
		$shipheroVendorChecker = new ShipheroVendorsDiffChecker();
		$apiVendorsData = '[{
            "node": {
              "name": "EyeBlack",
              "legacy_id": 14924,
              "id": "VmVuZG9yOjE0OTI0",
              "email": null,
              "account_number": null
            }
          },
          {
            "node": {
              "name": "FUJIAN QUANZHOU HV",
              "legacy_id": 337748,
              "id": "VmVuZG9yOjMzNzc0OA==",
              "email": "ruby@hvtex.com",
              "account_number": null
            }
          },
          {
            "node": {
              "name": "Fujian Yatong Shoes",
              "legacy_id": 156677,
              "id": "VmVuZG9yOjE1NjY3Nw==",
              "email": "jimmyzhu@vip.163.com",
              "account_number": null
            }
          },
          {
            "node": {
              "name": "Good People Sports",
              "legacy_id": 69442,
              "id": "VmVuZG9yOjY5NDQy",
              "email": "953440032@qq.com",
              "account_number": null
            }
        }]';
        $apiVendorsData = json_decode($apiVendorsData);
        $vendorsDiff = $shipheroVendorChecker->checkDiff($apiVendorsData);
        $this->assertEquals("VmVuZG9yOjE0OTI0",$vendorsDiff[0]->node->id);
	}


	public function testCheckDiffBetweenApiAndLocalStorage(){

		$shipheroVendorChecker = new ShipheroVendorsDiffChecker();
		

        $vendorsDiff = $shipheroVendorChecker->checkDiff($this->gqlVendorsRequest->data->vendors->data->edges);
        $this->assertGreaterThan(40,count($vendorsDiff));

	}

	public function testAddDiffVendorsToDb(){

		$shipheroVendorChecker = new ShipheroVendorsDiffChecker();
    $vendorsDiff = $shipheroVendorChecker->checkDiff($this->gqlVendorsRequest->data->vendors->data->edges);
    foreach($vendorsDiff as $apiVendor){
      $localVendor = new Vendor();
      $localVendor->idsp = $apiVendor->node->id;
      $localVendor->name = $apiVendor->node->name;
      $localVendor->legacy_idsp = $apiVendor->node->legacy_id;
      if ($apiVendor->node->email == null || $apiVendor->node->email == ''){
      	$apiVendor->node->email = 'xxxxxxx@xxxxx.com';
      }
      $localVendor->email = $apiVendor->node->email;
      $localVendor->save();
    }
    $localDBVendors = Vendor::all();
    $this->assertGreaterThan(40,$localDBVendors->count());

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