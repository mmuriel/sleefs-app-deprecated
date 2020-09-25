<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use setasign\Fpdi\Fpdi;

use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Helpers\Shopify\ProductNameToDirectoryNormalizer;
use Sleefs\Helpers\Shopify\VariantTitleToFileNameNormalizer;
use Sleefs\Helpers\Shopify\ProductNameToDirectoryChecker;
use Sleefs\Helpers\SleefsPdfStickerGenerator;


class CheckingPdfFilesFromProductsTest extends TestCase {


    public $prd,$var1,$var2,$var3,$prd2,$var4,$var5;

	public function setUp(){
        parent::setUp();
        $this->prepareForTests();

        $this->prd = new Product();
        $this->prd->idsp = "shpfy_2022501843037";
        $this->prd->title = "God's Plan Black Sticker for Back Plate &";
        $this->prd->vendor = 'Sleefs';
        $this->prd->product_type = 'Back Plate Decal';
        $this->prd->handle = 'gods-plan-back-plate-decal';
        $this->prd->save();

        $this->var1 = new Variant();
        $this->var1->idsp = "shpfy_19990743482461";
        $this->var1->sku = 'SL-GODPLN01-BPD';
        $this->var1->title = 'Battle Adult Chrome Back Bone / Black';
        $this->var1->idproduct = $this->prd->id;
        $this->var1->price = 15.0;
        $this->var1->save();

        $this->var2 = new Variant();
        $this->var2->idsp = "shpfy_19980086018141";
        $this->var2->sku = 'SL-GODPLN02-BPD';
        $this->var2->title = 'Battle Youth Chrome Back Bone / Black';
        $this->var2->idproduct = $this->prd->id;
        $this->var2->price = 15.00;
        $this->var2->save();

        $this->var3 = new Variant();
        $this->var3->idsp = "shpfy_20116650721373";
        $this->var3->sku = 'SL-GODPLN-BPD';
        $this->var3->title = 'Douglas Mr. DZ Back Plate / Black';
        $this->var3->idproduct = $this->prd->id;
        $this->var3->price = 15.00;
        $this->var3->save();


        //====================================================================


        $this->prd2 = new Product();
        $this->prd2->idsp = "shpfy_4525493092445";
        $this->prd2->title = "Verified Visor Skin";
        $this->prd2->vendor = 'Sleefs';
        $this->prd2->product_type = 'Visor Skin';
        $this->prd2->handle = 'verified-visor-skin';
        $this->prd2->save();

        $this->var4 = new Variant();
        $this->var4->idsp = "shpfy_31908739350621";
        $this->var4->sku = 'SL-VFIED-VD';
        $this->var4->title = 'SLEEFS (Purchased before 4/25/19) / White/LightBlue/Black';
        $this->var4->idproduct = $this->prd2->id;
        $this->var4->price = 15.0;
        $this->var4->save();

        $this->var5 = new Variant();
        $this->var5->idsp = "shpfy_31908739416157";
        $this->var5->sku = 'SL-VFIED-VD';
        $this->var5->title = 'SLEEFS X / White/LightBlue/Black';
        $this->var5->idproduct = $this->prd2->id;
        $this->var5->price = 15.00;
        $this->var5->save();
      
    }

	// Preparing the Test 
    public function testCorrectProductNameToCreateDirectory(){

        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $normalizedName = $this->prd->title;
        $normalizedName = $productNameNormalizer->normalizeProductName($normalizedName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));

        //Assertions
        $this->assertTrue(!preg_match("/[^a-zA-Z0-9\ \-&]/",$normalizedName),"El nombre del producto incluye caracteres diferentesa: A-Z, a-z, 0-9, (caracter espacio), - ");
        $this->assertEquals("Gods Plan Black Sticker for Back Plate AND",$normalizedName,"Error validando el texto: ".$normalizedName);

    }

    public function testCorrectVariantTitleToCreatePdfFile()
    {

        $variantTilteNormalizer = new VariantTitleToFileNameNormalizer();
        $normalizedTitle = $this->var1->title;
        $normalizedTitle = $variantTilteNormalizer->normalizeVariantTitle($normalizedTitle,array("/[^a-zA-Z0-9\ \-\/]/","/\//"),array("","--"));

        //Assertions
        $this->assertTrue(!preg_match("/[^a-zA-Z0-9\ \-\/]/",$normalizedTitle),"El titulo de la variante incluye caracteres diferentesa: A-Z, a-z, 0-9, (caracter espacio), - ");
        $this->assertEquals("Battle Adult Chrome Back Bone -- Black",$normalizedTitle,"Error validando el texto: ".$normalizedTitle);

    }


    public function testIfDirectoryAlreadyExist()
    {
        $pathToFolder = base_path()."/app/Sleefs/Docs/dropbox/";
        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $productDirectoryChecker = new ProductNameToDirectoryChecker();

        $normalizedName = $this->prd->title;
        $normalizedName = $productNameNormalizer->normalizeProductName($normalizedName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));

        $resp = $productDirectoryChecker->isDirectoryAlreadyCreated($pathToFolder.$normalizedName);

        $this->assertTrue($resp->value,$resp->notes);
        $this->assertEquals("La carpeta (".$pathToFolder.$normalizedName.") ya existe en el sistema de archivos",$resp->notes);
    }


    public function testCreateDirectoryByProductName()
    {
        $pathToFolder = base_path()."/app/Sleefs/Docs/dropbox/";
        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $productDirectoryChecker = new ProductNameToDirectoryChecker();

        $normalizedName=$this->prd2->title;
        $normalizedName=$productNameNormalizer->normalizeProductName($normalizedName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));

        
        $resp = $productDirectoryChecker->isDirectoryAlreadyCreated($pathToFolder.$normalizedName);
        if ($resp->value == false)
        {
            $dirCreation = mkdir($pathToFolder.$normalizedName);
            $this->assertTrue($dirCreation);
        }
        else
        {
            rmdir($pathToFolder.$normalizedName);
            $dirCreation = mkdir($pathToFolder.$normalizedName);
            $this->assertTrue($dirCreation);            
        }
        rmdir($pathToFolder.$normalizedName);
    }




    public function testCreatePdfFromVariantTitle()
    {
        $pathToFolder = base_path()."/app/Sleefs/Docs/dropbox/";
        $productNameNormalizer = new ProductNameToDirectoryNormalizer();
        $productDirectoryChecker = new ProductNameToDirectoryChecker();
        $variantTitleNormalizer = new VariantTitleToFileNameNormalizer();
        $sleefsPdfGen = new SleefsPdfStickerGenerator();
        

        $normalizedName=$this->prd2->title;
        $normalizedName=$productNameNormalizer->normalizeProductName($normalizedName,array("/[^a-zA-Z0-9\ \-&]/","/&/"),array("","AND"));

        $resp = $productDirectoryChecker->isDirectoryAlreadyCreated($pathToFolder.$normalizedName);
        if ($resp->value == false)
        {
            $dirCreation = mkdir($pathToFolder.$normalizedName);
            $this->assertTrue($dirCreation);
        }

        //It creates de PDF for variants
        $normalizedVariantTitle = '';
        foreach ($this->prd2->variants as $variant){
            $fpdi = new Fpdi();
            $normalizedVariantTitle=$variantTitleNormalizer->normalizeVariantTitle($variant->title,array("/[^a-zA-Z0-9\ \-&\/]/","/&/","/\//"),array("","AND","--"));
            //echo "\n".$variant->title."\n".$normalizedVariantTitle."\n---------------\n";
            $resCreatePdf = $sleefsPdfGen->createPdfFile($fpdi,'',$normalizedVariantTitle,$pathToFolder.$normalizedName);
            $this->assertTrue(file_exists($resCreatePdf->notes));
            unlink($resCreatePdf->notes);
        }
        rmdir ($pathToFolder.$normalizedName);
    }



	public function createApplication(){
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

     //
     // Migrates the database and set the mailer to 'pretend'.
     // This will cause the tests to run quickly.
     //

    private function prepareForTests(){
		//---------------------------------------------------------------
		//Real data testing
		//---------------------------------------------------------------
        \Artisan::call('migrate');

    }

}