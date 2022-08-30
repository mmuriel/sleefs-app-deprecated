<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Controllers\AutomaticProductPublisher;
use Sleefs\Helpers\Shiphero\SkuRawCollection;
use Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shiphero\InventoryReport;
use Sleefs\Models\Shiphero\InventoryReportItem;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\Shopify\ProductGetterBySku;
use Sleefs\Helpers\Shopify\QtyOrderedBySkuGetter;
use Sleefs\Helpers\Shiphero\ShipheroDailyInventoryReport;
use Sleefs\Helpers\Shopify\ProductPublishValidatorByImage;
use Sleefs\Helpers\Shopify\ProductTaggerForNewResTag;
use Sleefs\Helpers\ShopifyAPI\Shopify;
use Sleefs\Helpers\ShopifyAPI\RemoteProductGetterBySku;
use Sleefs\Helpers\FindifyAPI\Findify;



class ShopifyProductPublisherTest extends TestCase {


	private $products = array();
    private $variants = array();

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
      
    }


    public function testDummy(){

        $this->assertTrue(true);
    }
 
    /*
    
    //======================================
    //SE OMITEN TODOS LOS TEST DE ESTE COMPONENTE, YA QUE FUE RECHAZADO PARA PRODUCCION POR @JaimeSchuster
    //2019-09-24
    //======================================

    */

	// Preparing the Test 

	public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

     //
     // Migrates the database and set the mailer to 'pretend'.
     // This will cause the tests to run quickly.
     //

    private function prepareForTests()
    {

     	\Artisan::call('migrate');
     	Shiphero::setKey(env('SHIPHERO_APIKEY'));



     	// Adding data to database 
     	//Product #1
     	array_push($this->products,new Product());
		$this->products[0]->idsp = 'shpfy_1558599696496';
		$this->products[0]->title = '100 Emoji Red Arm Sleeve';
		$this->products[0]->vendor = 'Sleefs';
		$this->products[0]->product_type = 'Sleeve';
		$this->products[0]->handle = '100-emoji-red-arm-sleeve';
		$this->products[0]->save();

		array_push($this->variants,new Variant());
		$this->variants[0]->idsp = 'shpfy_15258428440688';
		$this->variants[0]->sku = 'SL-10EJIRD-AS-Y';
		$this->variants[0]->title = 'Y / Red';
		$this->variants[0]->idproduct = $this->products[0]->id;
		$this->variants[0]->price = 25.0;
		$this->variants[0]->save();

		
		//Product #2
		array_push($this->products,new Product());
		$this->products[1]->idsp = 'shpfy_5747890311';
		$this->products[1]->title = '100 Emoji Motivational Wristband';
		$this->products[1]->vendor = 'Sleefs';
		$this->products[1]->product_type = 'Wristband';
		$this->products[1]->handle = '100-emoji-wristband';
		$this->products[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[1]->idsp = 'shpfy_46556328714';
		$this->variants[1]->sku = 'SL-100-BLK-GLD-WB';
		$this->variants[1]->title = 'Black/gold';
		$this->variants[1]->idproduct = $this->products[1]->id;
		$this->variants[1]->price = 5.0;
		$this->variants[1]->save();


		//Product #3
		array_push($this->products,new Product());
		$this->products[2]->idsp = 'shpfy_9547409418';
		$this->products[2]->title = 'Aces Floral black quick-dry jersey';
		$this->products[2]->vendor = 'Sleefs';
		$this->products[2]->product_type = 'Jersey';
		$this->products[2]->handle = 'ace-floral-black-quick-dry-jersey';
		$this->products[2]->save();

		array_push($this->variants,new Variant());
		$this->variants[2]->idsp = 'shpfy_20093518421';
		$this->variants[2]->sku = 'SL-ACE--FLBLK-JS-XXS';
		$this->variants[2]->title = 'Black/withe';
		$this->variants[2]->idproduct = $this->products[2]->id;
		$this->variants[2]->price = 25.0;
		$this->variants[2]->save();


        //Product #4 -> Para prueba full de publicacion, producto sin publicar pero con foto
        array_push($this->products,new Product());
        $this->products[3]->idsp = 'shpfy_2114720897';
        $this->products[3]->title = 'Savage Stars Tactical Arm Sleeve';
        $this->products[3]->vendor = 'Sleefs';
        $this->products[3]->product_type = 'Sleeve';
        $this->products[3]->handle = 'tactical-savage-arm-sleeve';
        $this->products[3]->save();

        array_push($this->variants,new Variant());
        $this->variants[3]->idsp = 'shpfy_1640924298';
        $this->variants[3]->sku = 'SL-SAV-SBD-Y-1';
        $this->variants[3]->title = 'Y / Black/Gray';
        $this->variants[3]->idproduct = $this->products[3]->id;
        $this->variants[3]->price = 5.0;
        $this->variants[3]->save();


        //Product #5 -> Para prueba full de publicacion, producto sin publicar pero con foto
        array_push($this->products,new Product());
        $this->products[4]->idsp = 'shpfy_1826816093';
        $this->products[4]->title = 'Black Diamond Helmet Eye-Shield Visor';
        $this->products[4]->vendor = 'Sleefs';
        $this->products[4]->product_type = 'Visor';
        $this->products[4]->handle = 'black-diamond-helmet-eye-shield-visor';
        $this->products[4]->save();

        array_push($this->variants,new Variant());
        $this->variants[4]->idsp = 'shpfy_1143373731';
        $this->variants[4]->sku = 'SL-BLK-VS';
        $this->variants[4]->title = 'Black';
        $this->variants[4]->idproduct = $this->products[4]->id;
        $this->variants[4]->price = 60.0;
        $this->variants[4]->save();


        //Product #6 -> Para prueba full de publicacion, producto sin publicar pero con foto y nuevo (nuevo en 20190412)
        array_push($this->products,new Product());
        $this->products[5]->idsp = 'shpfy_1986958360669';
        $this->products[5]->title = 'Black Diamond Helmet Eye-Shield Visor';
        $this->products[5]->vendor = 'Sleefs';
        $this->products[5]->product_type = 'Wide Headband';
        $this->products[5]->handle = 'against-all-odds-red-headband';
        $this->products[5]->save();

        array_push($this->variants,new Variant());
        $this->variants[5]->idsp = 'shpfy_19668871413853';
        $this->variants[5]->sku = 'SL-REDAAO-WH';
        $this->variants[5]->title = 'ONE SIZE / Red';
        $this->variants[5]->idproduct = $this->products[5]->id;
        $this->variants[5]->price = 13.0;
        $this->variants[5]->save();


		//---------------------------------------------------------------
		//Real data testing
		//---------------------------------------------------------------

    }

}