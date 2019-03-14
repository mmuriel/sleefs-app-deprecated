<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;

use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;

use \mdeschermeier\shiphero\Shiphero;


class PosReportTest extends TestCase {

	public $po,$item1,$item2;

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();


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


		$this->item3 = new PurchaseOrderItem();
		$this->item3->idpo = $this->po->id;
		$this->item3->sku = 'SL-BB-LC-SH-L';
		$this->item3->shid = '58a6fd953ae30';
		$this->item3->quantity = 5;
		$this->item3->quantity_received = 0;
		$this->item3->name = 'Baseball lace shorts L / White/Black';
		$this->item3->idmd5 = md5('SL-BB-LC-SH-L'.'-'.'515');
		$this->item3->save();

		$this->item4 = new PurchaseOrderItem();
		$this->item4->idpo = $this->po->id;
		$this->item4->sku = 'SL-LITACUTY-WH';
		$this->item4->shid = '5a15a929e9323';
		$this->item4->quantity = 35;
		$this->item4->quantity_received = 10;
		$this->item4->name = 'Lion Tactical USA Tryton Ultra Double-Side Wide Headband One size fits all / Gray/Black';
		$this->item4->idmd5 = md5('SL-LITACUTY-WH'.'-'.'515');
		$this->item4->save();

		$this->item5 = new PurchaseOrderItem();
		$this->item5->idpo = $this->po->id;
		$this->item5->sku = 'SL-DIGI-RWB-Y-1';
		$this->item5->shid = '05286df4ee8c205f8070';
		$this->item5->quantity = 20;
		$this->item5->quantity_received = 0;
		$this->item5->name = 'Digital Camo American Arm Sleeve Single / Y / Red/White/Blue';
		$this->item5->idmd5 = md5('SL-DIGI-RWB-Y-1'.'-'.'515');
		$this->item5->save();

		$this->item6 = new PurchaseOrderItem();
		$this->item6->idpo = $this->po->id;
		$this->item6->sku = 'SL-OCWA-GN-AS-Y';
		$this->item6->shid = '3a9758818f8475cac5b1';
		$this->item6->quantity = 56;
		$this->item6->quantity_received = 56;
		$this->item6->name = 'Oceanic Warrior Gold Navy Arm Sleeve Y / Gold/Navy';
		$this->item6->idmd5 = md5('SL-OCWA-GN-AS-Y'.'-'.'515');
		$this->item6->save();
    }
 

	public function testImagesUrlAdjuster(){		

		/* Testing saved items to database */
		$urls = [
			'https://cdn.shopify.com/s/files/1/0282/4738/products/Prove_em_Wrong_Headband_Blue_9f2374f4-ec33-44e3-ac63-b7002ce55c2c.jpg?v=1539370351',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/Lets_Eat_Red_Spats_02.jpg?v=1537467760',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/DETAIL_Elastic_5580524a-aefd-4be9-ae10-c2fb17b21a22.jpg?v=1544462489',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/black-headband-no-tag.jpg?v=1519661738',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/Electric_Yellow_Spats.jpg?v=1526318458',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/pr.jpg?v=1517848248',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/I_Got_Your_Six_Wide_Headband_01_dbc391ec-ca0e-4656-95a4-d391b0de9b38.jpg?v=1517957138',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/White_Arm_Sleeve_01.jpg?v=1525963727',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/Pancakes_Wristbands.jpg?v=1543524249',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/AND_CHILL_BASKETBALL_1.jpeg?v=1494944368',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/Uses_Gray_080a5220-5e8d-41a5-be00-122f3db6e78b.png?v=1519420522',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/head-n-nek_0f380ea1-0e98-48f9-b180-afacad32e902.png?v=1517412555',
			'https://cdn.shopify.com/s/files/1/0282/4738/products/head-n-nek_grande_grande_81cf119a-2b7a-4823-aff4-a991191be109.png?v=1545262149'
		];

		$urlImgGenerator = new ImageUrlBySizeGenerator();
		foreach ($urls as $url){
			$finalUrl = $urlImgGenerator->createImgUrlWithSizeParam($url,150,150);
			$this->assertRegexp('/_150x150\.(jpg|jpeg|png|gif)\?v=([0-9]{8,15})/',$finalUrl);
			
		}

		
	}


	public function testGetPoItemsOrderedByName(){

		$po = PurchaseOrder::whereRaw("po_id='515'")->first();

		/*
		foreach($po->items as $item){
			echo $item->name."\n";
		}
		*/
		$po->items = $po->items()->orderBy('name')->get();
		/*
		//echo "\n========================\n";
		foreach($po->items as $item){
			echo $item->name."\n";
		}
		*/
		$this->assertEquals('SL-BB-LC-SH-L',$po->items->get(0)->sku);
	}

	/* Preparing the Test */

	public function createApplication(){
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

     /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     */
    private function prepareForTests(){
    	\Artisan::call('migrate');
    }

}