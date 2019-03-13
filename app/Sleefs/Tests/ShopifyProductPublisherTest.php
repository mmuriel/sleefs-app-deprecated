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
use Sleefs\Helpers\Shopify\ProductPublishValidatorByImage;
use Sleefs\Helpers\ShopifyAPI\Shopify;
use Sleefs\Helpers\ShopifyAPI\RemoteProductGetterBySku;


use Sleefs\Helpers\Shopify\ProductTaggerForNewResTag;

class ShopifyProductPublisherTest extends TestCase {


	private $products = array();
    private $variants = array();

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
      
    }
 

    public function testShopifyPublishedProduct(){

    	$shopifyProductPublisher = new ProductPublishValidatorByImage();

    	//Defining product #1 it must fail for validation
    	$productRaw1 = new \stdClass();
    	$productRaw1->id = 1558599696496;
		$productRaw1->handle = "100-emoji-red-arm-sleeve";
		$productRaw1->published_at = null;
		$productRaw1->images = [];

		$validationResult1 = $shopifyProductPublisher->isProductReadyToPublish($productRaw1);
    	
		//Defining product #2 it must pass for validation
    	$productRaw2 = new \stdClass();
    	$productRaw2->id = 12275921994;
		$productRaw2->handle = "1-asterisk-thin-blue-line-wristband";
		$productRaw2->published_at = '2017-10-03T09:33:55-04:00';

		$img1 = new \stdClass();
		$img1->id = 102226395146;
		$img1->product_id = 12275921994;
		$img1->position = 1;
		$img1->created_at = "2017-10-03T09:33:28-04:00";
		$img1->updated_at = "2017-10-03T09:33:28-04:00";
		$img1->alt = null;
		$img1->width = 1600;
		$img1->height = 1600;
		$img1->src = "https://cdn.shopify.com/s/files/1/0282/4738/products/1_Asterisk_Thin_Blue_Line_Wristband.jpg?v=1507037608";
		$img1->variant_ids = [50046707210];
		$img1->admin_graphql_api_id = "gid://shopify/ProductImage/102226395146";


		$img2 = new \stdClass();
		$img2->id = 102226657290;
		$img2->product_id = 12275921994;
		$img2->position = 2;
		$img2->created_at = "2017-10-03T09:33:29-04:00";
		$img2->updated_at = "2017-10-03T09:33:29-04:00";
		$img2->alt = null;
		$img2->width = 1600;
		$img2->height = 1600;
		$img2->src = "https://cdn.shopify.com/s/files/1/0282/4738/products/ALL_White_1200x1200000_75695341-bd89-48f6-9649-6f2730e3e99e.jpg?v=1507037609";
		$img2->variant_ids = [];
		$img2->admin_graphql_api_id = "gid://shopify/ProductImage/102226657290";

		$productRaw2->images = [$img1,$img2];
    	$validationResult2 = $shopifyProductPublisher->isProductReadyToPublish($productRaw2);

    	//Defining product #2 it must pass for validation
    	$productRaw3 = new \stdClass();
    	$productRaw3->id = 12275921708;
		$productRaw3->handle = "2-asterisk-thin-blue-line-wristband";
		$productRaw3->published_at = null;

		$img3 = new \stdClass();
		$img3->id = 102226395149;
		$img3->product_id = 12275921708;
		$img3->position = 1;
		$img3->created_at = "2018-11-21T09:13:40-04:00";
		$img3->updated_at = "2018-11-21T09:13:40-04:00";
		$img3->alt = null;
		$img3->width = 1600;
		$img3->height = 1600;
		$img3->src = "https://cdn.shopify.com/s/files/1/0282/4738/products/1_Asterisk_Thin_Blue_Line_Wristband.jpg?v=1507037608";
		$img3->variant_ids = [50046707210];
		$img3->admin_graphql_api_id = "gid://shopify/ProductImage/102226395146";

		$productRaw3->images = [$img3];
    	$validationResult3 = $shopifyProductPublisher->isProductReadyToPublish($productRaw3);


    	//Asserting product #1
    	$this->assertFalse($validationResult1->value);
    	$this->assertEquals('No images',$validationResult1->notes);

    	//Asserting product #2
    	//var_dump($validationResult2);
    	$this->assertFalse($validationResult2->value);
    	$this->assertEquals('Product already published',$validationResult2->notes);

    	//Asserting product #3
    	$this->assertTrue($validationResult3->value);
    	$this->assertEquals('Product ready to be published',$validationResult3->notes);

    }



    public function testGetRemoteProductBySku(){

    	$productGetter = new ProductGetterBySku();
    	$shopifyApi = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));


    	//============================================================
    	// Testing for a "not ready to publish" product, Product #1
    	//============================================================
    	$localProduct1 = new \Sleefs\Models\Shopify\Product();
    	$localProduct1 = $productGetter->getProduct('SL-ACE--FLBLK-JS-XXS',$localProduct1);
    	$this->assertEquals('9547409418',$localProduct1->idsp);
    	$options1 = 'handle='.$localProduct1->handle.'&fields=id,handle,published_at,images';

    	$remoteRaw1 = $shopifyApi->getAllProducts($options1);
    	if (count($remoteRaw1->products) >= 1){

    		$productRaw1 = $remoteRaw1->products[0];
    		$this->assertEquals(null,$productRaw1->published_at);

    	}else{
    		$this->assertTrue(false);
    	}

    	//============================================================
    	// Testing for a "ready to publish" product, Product #2
    	//============================================================
    	$localProduct2 = new \Sleefs\Models\Shopify\Product();
    	$localProduct2 = $productGetter->getProduct('SL-100-BLK-GLD-WB',$localProduct2);
    	$this->assertEquals('5747890311',$localProduct2->idsp);
    	$options2 = 'handle='.$localProduct2->handle.'&fields=id,handle,published_at,images';

    	$remoteRaw2 = $shopifyApi->getAllProducts($options2);
    	if (count($remoteRaw2->products) >= 1){

    		$productRaw2 = $remoteRaw2->products[0];
    		$this->assertEquals(null,$productRaw2->published_at);
    		$this->assertEquals(6,count($productRaw2->images));

    	}else{
    		$this->assertTrue(false);
    	}
    }


    public function testRemoteProductGetterBySkuClass(){

    	$remoteProductGetter =new RemoteProductGetterBySku();
    	$shopifyApi = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));
    	$shopifyProductPublisher = new ProductPublishValidatorByImage();

    	$remoteProduct1 = $remoteProductGetter->getRemoteProductBySku('SL-10EJIRD-AS-Y',$shopifyApi);
    	$this->assertNotNull($remoteProduct1);

		$validationResult1 = $shopifyProductPublisher->isProductReadyToPublish($remoteProduct1);
		$this->assertFalse($validationResult1->value);

		$remoteProduct2 = $remoteProductGetter->getRemoteProductBySku('SL-100-BLK-GLD-WB',$shopifyApi);
    	$this->assertNotNull($remoteProduct2);

		$validationResult2 = $shopifyProductPublisher->isProductReadyToPublish($remoteProduct2);
		$this->assertTrue($validationResult2->value);

    }



    public function testTagProductWithNEWTag(){

    	$shopifyApi = new Shopify('6d79f49e6c91cb45eb5e37270f527afa','f6c18f765183ef32b76ebf9824dd8311','sleefs-preorder.myshopify.com/admin/');
    	$rawProduct = new \stdClass();
    	$rawProduct->id = 255606456350;
    	$rawProduct->title = "Blue Mask Thin Blue Line Compression Tights / Leggings (PRE-ORDER)*";
    	$rawProduct->vendor = "SLEEFS";
    	//$rawProduct->created_at = "2017-10-24T11:41:44-04:00";
    	$rawProduct->created_at = date("Y-m-d",strtotime("-15 days"))."T00:01:44-04:00";
    	$rawProduct->handle = "blue-mask-thin-blue-line-compression-tights-leggings";
    	$rawProduct->tags = "alltights, BlackColor, BlueColor, Mask, Police, testing, Thin Blue Line, thinblueline, tights, Warrior";
    	$rawProduct->product_type = "Tights";
    	$rawProduct->updated_at = "2018-03-25T23:07:16-04:00";
    	$rawProduct->template_suffix = null;
    	//$rawProduct->published_at = "2017-10-24T11:44:54-04:00";
    	$rawProduct->published_at = null;
    	
    	$options = new \stdClass();
    	$tagger = new ProductTaggerForNewResTag();

    	$tag = $tagger->defineTag ($rawProduct);
    	$rawProduct = $tagger->tagProduct($rawProduct,$shopifyApi,$options);
    	$this->assertRegExp("/^NEW[0-9]{6,6}/",$tag);
    	$this->assertRegExp("/(NEW[0-9]{6,6})/",$rawProduct->tags);
    	//echo "\nTag Line #1: ".$rawProduct->tags."\n";
    	//$tagger->tagProduct($rawProduct,$shopifyApi,$options);

    }




    public function testTagProductWithRESTag(){

    	$shopifyApi = new Shopify('6d79f49e6c91cb45eb5e37270f527afa','f6c18f765183ef32b76ebf9824dd8311','sleefs-preorder.myshopify.com/admin/');
    	$rawProduct = new \stdClass();
    	$rawProduct->id = 255605014558;
    	$rawProduct->title = "Blessed Black Compression Tights / Leggings (PRE-ORDER)*";
    	$rawProduct->vendor = "SLEEFS";
    	$rawProduct->created_at = "2017-10-24T11:41:44-04:00";
    	$rawProduct->handle = "blessed-black-compression-tights-leggings";
    	$rawProduct->tags = "alltights, BlackColor, blessed, Blessing, blessings, testing, tights, WhiteColor";
    	$rawProduct->product_type = "Tights";
    	$rawProduct->updated_at = "2018-03-25T23:07:16-04:00";
    	$rawProduct->template_suffix = null;
    	//$rawProduct->published_at = "2017-10-24T11:44:54-04:00";
    	$rawProduct->published_at = null;
    
    	$options = new \stdClass();
    	$tagger = new ProductTaggerForNewResTag();

    	$tag = $tagger->defineTag ($rawProduct);
    	$rawProduct = $tagger->tagProduct($rawProduct,$shopifyApi,$options);
    	$this->assertRegExp("/^RES[0-9]{6,6}/",$tag);
    	$this->assertRegExp("/(RES[0-9]{6,6})/",$rawProduct->tags);
    	//echo "\nTag Line #2: ".$rawProduct->tags."\n";
    	//$tagger->tagProduct($rawProduct,$shopifyApi,$options);


    	
    	$tagLine = $rawProduct->tags;
    	$tagLine = preg_replace("/(\ {0,1}NEW[0-9]{6,6}\,{0,1})/","",$tagLine);
        $tagLine = preg_replace("/(\ {0,1}RES[0-9]{6,6}\,{0,1})/","",$tagLine);
    	$shopifyApi->updateProduct($rawProduct->id,array('product'=>array(
            'tags'=>$tagLine)));
		
    }




	public function testTagProductNewToResTag(){

    	$shopifyApi = new Shopify('6d79f49e6c91cb45eb5e37270f527afa','f6c18f765183ef32b76ebf9824dd8311','sleefs-preorder.myshopify.com/admin/');
    	$rawProduct = $shopifyApi->getSingleProduct(255606456350);
    	$this->assertRegExp("/NEW([0-9]{6,6})/",$rawProduct->product->tags);
    	/*
    	$rawProduct = new \stdClass();
    	$rawProduct->id = 255605014558;
    	$rawProduct->title = "Blessed Black Compression Tights / Leggings (PRE-ORDER)*";
    	$rawProduct->vendor = "SLEEFS";
    	$rawProduct->created_at = "2017-10-24T11:41:44-04:00";
    	$rawProduct->handle = "blessed-black-compression-tights-leggings";
    	$rawProduct->tags = "alltights, BlackColor, blessed, Blessing, blessings, testing, tights, WhiteColor";
    	$rawProduct->product_type = "Tights";
    	$rawProduct->updated_at = "2018-03-25T23:07:16-04:00";
    	$rawProduct->template_suffix = null;
    	//$rawProduct->published_at = "2017-10-24T11:44:54-04:00";
    	$rawProduct->published_at = null;
    	//=====================================
    	*/
    	$options = new \stdClass();
    	$tagger = new ProductTaggerForNewResTag();

    	$tag = $tagger->defineTag ($rawProduct->product);
    	$rawProduct = $tagger->tagProduct($rawProduct->product,$shopifyApi,$options);
    	$this->assertRegExp("/^RES[0-9]{6,6}/",$tag);
    	$this->assertRegExp("/(RES[0-9]{6,6})/",$rawProduct->tags);
    	//echo "\nTag Line #3: ".$rawProduct->tags."\n";
    	//$tagger->tagProduct($rawProduct,$shopifyApi,$options);


    	
    	//Retornando el producto al estado natural:
    	$tagLine = $rawProduct->tags;
    	$tagLine = preg_replace("/(\ {0,1}NEW[0-9]{6,6}\,{0,1})/","",$tagLine);
        $tagLine = preg_replace("/(\ {0,1}RES[0-9]{6,6}\,{0,1})/","",$tagLine);
    	$shopifyApi->updateProduct($rawProduct->id,array('product'=>array(
            'tags'=>$tagLine)));
		
    }




    public function testTagProductErrorTry(){

    	$shopifyApi = new Shopify('6d79f49e6c91cb45eb5e37270f527afa','f6c18f765183ef32b76ebf9824dd8311','sleefs-preorder.myshopify.com/admin/');
    	$rawProduct = new \stdClass();
    	$rawProduct->id = 155605014558;//This is a not valid product, this ID doesn't exists
    	$rawProduct->title = "Blessed Black Compression Tights / Leggings (PRE-ORDER)*";
    	$rawProduct->vendor = "SLEEFS";
    	$rawProduct->created_at = "2017-10-24T11:41:44-04:00";
    	$rawProduct->handle = "blessed-black-compression-tights-leggings";
    	$rawProduct->tags = "alltights, BlackColor, blessed, Blessing, blessings, testing, tights, WhiteColor";
    	$rawProduct->product_type = "Tights";
    	$rawProduct->updated_at = "2018-03-25T23:07:16-04:00";
    	$rawProduct->template_suffix = null;
    	$rawProduct->published_at = null;
    
    	$options = new \stdClass();
    	$tagger = new ProductTaggerForNewResTag();

    	$tag = $tagger->defineTag ($rawProduct);
    	$rawProduct = $tagger->tagProduct($rawProduct,$shopifyApi,$options);
    	//print_r($rawProduct);
    	$this->assertFalse($rawProduct);
    	//echo "\nTag Line #2: ".$rawProduct->tags."\n";
    	//$tagger->tagProduct($rawProduct,$shopifyApi,$options)
		
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



     	// Adding data to database 
     	//Product #1
     	array_push($this->products,new Product());
		$this->products[0]->idsp = 1558599696496;
		$this->products[0]->title = '100 Emoji Red Arm Sleeve';
		$this->products[0]->vendor = 'Sleefs';
		$this->products[0]->product_type = 'Sleeve';
		$this->products[0]->handle = '100-emoji-red-arm-sleeve';
		$this->products[0]->save();

		array_push($this->variants,new Variant());
		$this->variants[0]->idsp = 15258428440688;
		$this->variants[0]->sku = 'SL-10EJIRD-AS-Y';
		$this->variants[0]->title = 'Y / Red';
		$this->variants[0]->idproduct = $this->products[0]->id;
		$this->variants[0]->price = 25.0;
		$this->variants[0]->save();

		
		//Product #2
		array_push($this->products,new Product());
		$this->products[1]->idsp = 5747890311;
		$this->products[1]->title = '100 Emoji Motivational Wristband';
		$this->products[1]->vendor = 'Sleefs';
		$this->products[1]->product_type = 'Wristband';
		$this->products[1]->handle = '100-emoji-wristband';
		$this->products[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[1]->idsp = 46556328714;
		$this->variants[1]->sku = 'SL-100-BLK-GLD-WB';
		$this->variants[1]->title = 'Black/gold';
		$this->variants[1]->idproduct = $this->products[1]->id;
		$this->variants[1]->price = 5.0;
		$this->variants[1]->save();


		//Product #3
		array_push($this->products,new Product());
		$this->products[2]->idsp = 9547409418;
		$this->products[2]->title = 'Aces Floral black quick-dry jersey';
		$this->products[2]->vendor = 'Sleefs';
		$this->products[2]->product_type = 'Jersey';
		$this->products[2]->handle = 'ace-floral-black-quick-dry-jersey';
		$this->products[2]->save();

		array_push($this->variants,new Variant());
		$this->variants[2]->idsp = 20093518421;
		$this->variants[2]->sku = 'SL-ACE--FLBLK-JS-XXS';
		$this->variants[2]->title = 'Black/withe';
		$this->variants[2]->idproduct = $this->products[2]->id;
		$this->variants[2]->price = 25.0;
		$this->variants[2]->save();

		/*
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


		// Adding POs 

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
		
		*/

		//---------------------------------------------------------------
		//Real data testing
		//---------------------------------------------------------------

    }

}