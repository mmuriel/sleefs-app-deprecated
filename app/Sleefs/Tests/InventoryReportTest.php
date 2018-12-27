<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use \mdeschermeier\shiphero\Shiphero;
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

class InventoryReportTest extends TestCase {


	private $products = array();
    private $variants = array();
    private $pos = array();
	private $items = array();

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
      
    }
 
 	public function testGetting1000ProductsFromShiphero(){
	
 		$options = array('page'=>1, 'count'=>1000);
		$products = Shiphero::getProduct($options);
		//print_r($products);
		$this->assertEquals(1000,count($products->products->results),"No se han retornado 1000 productos");

		$prdsCollection = new SkuRawCollection();
		$prdsCollection->addElementsFromShipheroApi($products->products->results);
		$this->assertEquals(1000,$prdsCollection->count(),"La se esperan 0 y la colección tiene".$prdsCollection->count());

 	}

 	public function testGettingAllShipheroProducts(){
 		$shProductsGetter = new ShipheroAllProductsGetter();
 		$prdsCollection = new SkuRawCollection();
 		$prdsCollection = $shProductsGetter->getAllProducts(['apikey'=>env('SHIPHERO_APIKEY'),'qtyperpage'=>1000],$prdsCollection);
 		$this->assertGreaterThan(12000,$prdsCollection->count());
 		//print_r($prdsCollection->get('SL-WROWPP-AS-Y')); 		
 	}


 	public function testGetProductTypeBySku(){
 		$variant = Variant::find(1);
 		$productFinder = new ProductGetterBySku();
 		$product = new Product();
 		$product = $productFinder->getProduct($variant->sku,$product);
 		$this->assertEquals('100-emoji-black-tights-for-kids',$product->handle);
 		$this->assertEquals('Kids Tights',$product->product_type);
 	}


 	public function testGetAllQtyOrderedBySku(){
 		$qtyGetter = new QtyOrderedBySkuGetter();
 		$qtyOrderedBySku = $qtyGetter->getQtyOrdered('SL-10EJICK-KCL-YM');
 		$this->assertEquals(17,$qtyOrderedBySku);
 	}


 	public function testCreateInventoryReport(){
        $reportCreator = new ShipheroDailyInventoryReport();
        $report = $reportCreator->createReport(['apikey'=>env('SHIPHERO_APIKEY'),'qtyperpage'=>1000]);

        $this->assertEquals(2,$report->inventoryReportItems->count());
        $this->assertEquals(24,$report->inventoryReportItems->get(1)->total_on_order);
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
     	Shiphero::setKey(env('SHIPHERO_APIKEY'));


     	/* Adding data to database */
     	//Product #1
     	array_push($this->products,new Product());
		$this->products[0]->idsp = 890987645;
		$this->products[0]->title = '100 Emoji Black Tights for Kids';
		$this->products[0]->vendor = 'Sleefs';
		$this->products[0]->product_type = 'Kids Tights';
		$this->products[0]->handle = '100-emoji-black-tights-for-kids';
		$this->products[0]->save();

		array_push($this->variants,new Variant());
		$this->variants[0]->idsp = 5678890951;
		$this->variants[0]->sku = 'SL-10EJICK-KCL-YM';
		$this->variants[0]->title = 'YM / Black';
		$this->variants[0]->idproduct = $this->products[0]->id;
		$this->variants[0]->price = 25.0;
		$this->variants[0]->save();

		//Product #2
		array_push($this->products,new Product());
		$this->products[1]->idsp = 890987646;
		$this->products[1]->title = 'Aerial blue and navy arm sleeve';
		$this->products[1]->vendor = 'Sleefs';
		$this->products[1]->product_type = 'Sleeve';
		$this->products[1]->handle = 'aerial-blue-and-navy-arm-sleeve';
		$this->products[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[1]->idsp = 56788909561;
		$this->variants[1]->sku = 'SL-AERIB-KS-YL';
		$this->variants[1]->title = 'Y / Blue/navy';
		$this->variants[1]->idproduct = $this->products[1]->id;
		$this->variants[1]->price = 5.0;
		$this->variants[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[2]->idsp = 5678890962;
		$this->variants[2]->sku = 'SL-ARL-BLU-NVY-XL-1';
		$this->variants[2]->title = 'XL / Blue/navy';
		$this->variants[2]->idproduct = $this->products[1]->id;
		$this->variants[2]->price = 5.0;
		$this->variants[2]->save();


		//Product #3
		array_push($this->products,new Product());
		$this->products[2]->idsp = 890987647;
		$this->products[2]->title = 'Ripped Bear arm sleeve';
		$this->products[2]->vendor = 'Sleefs';
		$this->products[2]->product_type = 'Sleeve';
		$this->products[2]->handle = 'brasilian-sleeve-yellow';
		$this->products[2]->save();

		array_push($this->variants,new Variant());
		$this->variants[3]->idsp = 56788909571;
		$this->variants[3]->sku = 'SL-ANIM-BEAR-Y-1';
		$this->variants[3]->title = 'Y / Black/White';
		$this->variants[3]->idproduct = $this->products[2]->id;
		$this->variants[3]->price = 5.0;
		$this->variants[3]->save();

		array_push($this->variants,new Variant());
		$this->variants[4]->idsp = 56788909572;
		$this->variants[4]->sku = 'SL-ANIM-BEAR-XS-1';
		$this->variants[4]->title = 'XS / Black/White';
		$this->variants[4]->idproduct = $this->products[2]->id;
		$this->variants[4]->price = 5.0;
		$this->variants[4]->save();

		array_push($this->variants,new Variant());
		$this->variants[5]->idsp = 56788909573;
		$this->variants[5]->sku = 'SL-ANIM-BEAR-S-M-1';
		$this->variants[5]->title = 'S/M / Black/White';
		$this->variants[5]->idproduct = $this->products[2]->id;
		$this->variants[5]->price = 5.0;
		$this->variants[5]->save();

		array_push($this->variants,new Variant());
		$this->variants[6]->idsp = 56788909574;
		$this->variants[6]->sku = 'SL-ANIM-BEAR-L-1';
		$this->variants[6]->title = 'L / Black/White';
		$this->variants[6]->idproduct = $this->products[2]->id;
		$this->variants[6]->price = 5.0;
		$this->variants[6]->save();

		array_push($this->variants,new Variant());
		$this->variants[7]->idsp = 56788909575;
		$this->variants[7]->sku = 'SL-ANIM-BEAR-XL-1';
		$this->variants[7]->title = 'XL / Black/White';
		$this->variants[7]->idproduct = $this->products[2]->id;
		$this->variants[7]->price = 5.0;
		$this->variants[7]->save();

		//Product #4
		array_push($this->products,new Product());
		$this->products[3]->idsp = 890987648;
		$this->products[3]->title = 'Red Hat';
		$this->products[3]->vendor = 'Sleefs';
		$this->products[3]->product_type = 'Hat';
		$this->products[3]->handle = 'red-hat';
		$this->products[3]->save();

		array_push($this->variants,new Variant());
		$this->variants[6]->idsp = 56788909581;
		$this->variants[6]->sku = 'SL-REDHAT';
		$this->variants[6]->title = 'Red Hat';
		$this->variants[6]->idproduct = $this->products[3]->id;
		$this->variants[6]->price = 12.50;
		$this->variants[6]->save();


		/* Adding POs */

		//PO #1
		array_push($this->pos, new PurchaseOrder());
        $this->pos[0]->po_id = 515;
        $this->pos[0]->po_number = '1810-07 Re Order Kids Tights';
        $this->pos[0]->po_date = '2017-10-30 00:00:00';
        $this->pos[0]->fulfillment_status = 'pending';
		$this->pos[0]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[0]->idpo = $this->pos[0]->id;
		$this->items[0]->sku = 'SL-10EJICK-KCL-YM';
		$this->items[0]->shid = '59dbc5830f969';
		$this->items[0]->quantity = 5;
		$this->items[0]->quantity_received = 0;
		$this->items[0]->name = '100 Emoji Black Tights for Kids / YM / Black';
		$this->items[0]->idmd5 = md5('SL-10EJICK-KCL-YM'.'-'.'515');
		$this->items[0]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[1]->idpo = $this->pos[0]->id;
		$this->items[1]->sku = 'SL-ANIM-BEAR-Y-1';
		$this->items[1]->shid = '59dbc5830fa20';
		$this->items[1]->quantity = 3;
		$this->items[1]->quantity_received = 3;
		$this->items[1]->name = 'Ripped Bear arm sleeve / Y / Black/White';
		$this->items[1]->idmd5 = md5('SL-ANIM-BEAR-Y-1'.'-'.'515');
		$this->items[1]->save();


		//PO #2
		array_push($this->pos, new PurchaseOrder());
        $this->pos[1]->po_id = 516;
        $this->pos[1]->po_number = 'MMA PO 1';
        $this->pos[1]->po_date = '2017-12-30 21:29:00';
        $this->pos[1]->fulfillment_status = 'pending';
		$this->pos[1]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[2]->idpo = $this->pos[1]->id;
		$this->items[2]->sku = 'SL-10EJICK-KCL-YM';
		$this->items[2]->shid = '69d3c5830f969';
		$this->items[2]->quantity = 12;
		$this->items[2]->quantity_received = 3;
		$this->items[2]->name = '100 Emoji Black Tights for Kids / YM / Black';
		$this->items[2]->idmd5 = md5('SL-10EJICK-KCL-YM'.'-'.'516');
		$this->items[2]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[3]->idpo = $this->pos[1]->id;
		$this->items[3]->sku = 'SL-AERIB-KS-YL';
		$this->items[3]->shid = '62c35a8302a86';
		$this->items[3]->quantity = 21;
		$this->items[3]->quantity_received = 21;
		$this->items[3]->name = 'Aerial blue and navy arm sleeve / Y / Blue/navy';
		$this->items[3]->idmd5 = md5('SL-AERIB-KS-YL'.'-'.'516');
		$this->items[3]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[4]->idpo = $this->pos[1]->id;
		$this->items[4]->sku = 'SL-REDHAT';
		$this->items[4]->shid = '1aa8217bd792f';
		$this->items[4]->quantity = 23;
		$this->items[4]->quantity_received = 20;
		$this->items[4]->name = 'SL-REDHAT';
		$this->items[4]->idmd5 = md5('SL-REDHAT'.'-'.'516');
		$this->items[4]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[5]->idpo = $this->pos[1]->id;
		$this->items[5]->sku = 'SL-ANIM-BEAR-L-1';
		$this->items[5]->shid = '3149adc003ed9';
		$this->items[5]->quantity = 5;
		$this->items[5]->quantity_received = 0;
		$this->items[5]->name = 'Ripped Bear arm sleeve / L / Black/White';
		$this->items[5]->idmd5 = md5('SL-ANIM-BEAR-L-1'.'-'.'516');
		$this->items[5]->save();


		//---------------------------------------------------------------
		//Real data testing
		//---------------------------------------------------------------

    }

}