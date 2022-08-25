<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;


use Sleefs\Helpers\ShopifyAPI\Shopify;

class ShopifyApiTest extends TestCase {

	public $prd,$var1,$var2;

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();

        $this->prd = new Product();
		$this->prd->idsp = "shpfy_890987645";
		$this->prd->title = 'Colombian Sleeve Yellow';
		$this->prd->vendor = 'Sleefs';
		$this->prd->product_type = 'Sleeve';
		$this->prd->handle = 'colombian-sleeve-yellow';
		$this->prd->save();

		$this->var1 = new Variant();
		$this->var1->idsp = "shpfy_5678890951";
		$this->var1->sku = 'SL-COL-Y-L';
		$this->var1->title = 'Large';
		$this->var1->idproduct = $this->prd->id;
		$this->var1->price = 12.50;
		$this->var1->save();

		$this->var2 = new Variant();
		$this->var2->idsp = "shpfy_5678890952";
		$this->var2->sku = 'SL-COL-Y-XL';
		$this->var2->title = 'XL';
		$this->var2->idproduct = $this->prd->id;
		$this->var2->price = 12.50;
		$this->var2->save();
    }
 

	public function testInmemoryDatabaseAddingRecords(){		

		/* Testing saved items to database */
		$this->assertDatabaseHas('products',['idsp' => 'shpfy_890987645','title' => 'Colombian Sleeve Yellow']);
		$this->assertDatabaseHas('variants',['idsp' => 'shpfy_5678890951','idsp' => 'shpfy_5678890952']);

		
	}

	public function testInmemoryDatabaseProductVariantsRelationship(){

		$tmpPrd = Product::where('title','=','Colombian Sleeve Yellow')->first();
		$this->assertEquals('Sleefs',$this->var1->product->vendor);
		$this->assertEquals('SL-COL-Y-L',$tmpPrd->variants[0]->sku);


	}


	
	public function testGetProductsFromApi(){

		$spClient = new Shopify('f7adb74791e9b142c7f6bc3a64bcc3b0','5486391dc27e857cfc1e8986b8094c12','sleefs-2.myshopify.com/admin/api/2020-01/');
		$options = "ids=431368941,10847934410";
		$data = $spClient->getAllProducts($options);

		$this->assertEquals("Baseball Lace USA Arm Sleeve",$data->products[0]->title,"El nombre del producto no es: Baseball Lace USA Arm Sleeve, ahora es: ".$data->products[0]->title);
		$this->assertEquals(1,count($data->products),"La cantidad de productos recuperada no es 2, es: ".count($data->products));
		$this->assertEquals("Sleeve",$data->products[0]->product_type);
		//count($data->products);

	}


	public function testGetVariantsFromApi(){

		$spClient = new Shopify('f7adb74791e9b142c7f6bc3a64bcc3b0','5486391dc27e857cfc1e8986b8094c12','sleefs-2.myshopify.com/admin/api/2020-01/');
		$options = "ids=431368941,10847934410";
		$data = $spClient->getAllProducts($options);
		//count($data->products);
		$variantRaw = $spClient->getSingleProductVariant($data->products[0]->variants[0]->id);
		$this->assertEquals('SL-BB-USA-Y-1',$variantRaw->variant->sku);

	}



	public function testGetSingleProductFromApi(){

		$spClient = new Shopify('f7adb74791e9b142c7f6bc3a64bcc3b0','5486391dc27e857cfc1e8986b8094c12','sleefs-2.myshopify.com/admin/api/2020-01/');
		$options = "ids=431368941,10847934410";
		$data = $spClient->getSingleProduct('431368941');
		$this->assertEquals('Baseball Lace USA Arm Sleeve',$data->product->title);

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