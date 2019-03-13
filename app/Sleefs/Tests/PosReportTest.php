<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;

use \mdeschermeier\shiphero\Shiphero;


class PosReportTest extends TestCase {

	public $po,$item1,$item2;

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
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
    private function prepareForTests(){
    }

}