<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use Sleefs\Helpers\SleefsPdfStickerGenerator;
use setasign\Fpdi\Fpdi;


class SleefsPdfProductsTest extends TestCase {

	public function setUp(){
        parent::setUp();
        $this->prepareForTests();
      
    }


    public function testCreatingPdfFileFromTemplate(){
        //New PDF file to copy
        $pdf = new Fpdi();
        // set the source file
        $pathToPdfFile = base_path()."/app/Sleefs/Docs/sample.pdf";
        //echo "\n[MMA]: ".$pathToPdfFile;
        $pdfGen = new SleefsPdfStickerGenerator();
        $orderId = 'SL67209821';
        $pdfDestPath = base_path()."/app/Sleefs/Docs/".date("Ymd");
        if(!is_dir($pdfDestPath)){
            mkdir($pdfDestPath);
        }


        //It clears previous pdf file created
        if (file_exists($pdfDestPath."/".$orderId.".pdf"))
            unlink ($pdfDestPath."/".$orderId.".pdf");

        $response = $pdfGen->createPdfFile($pdf,$pathToPdfFile,$orderId,$pdfDestPath);

        

        $this->assertTrue(file_exists($response->notes));
        $this->assertRegExp("/(".$orderId."\.pdf){1,1}$/",$response->notes);
        unlink ($pdfDestPath."/".$orderId.".pdf");
        rmdir ($pdfDestPath);
    }


    public function testCreatePdfBlankFile(){

        $pdfGen = new SleefsPdfStickerGenerator();
        $pdf = new Fpdi();
        $pdfDestPath = base_path()."/app/Sleefs/Docs/".date("Ymd");
        $orderId = 'SL67209821';

        if(!is_dir($pdfDestPath))
        {
            mkdir($pdfDestPath);
        }

        if (file_exists($pdfDestPath."/".$orderId.".pdf"))
            unlink ($pdfDestPath."/".$orderId.".pdf");

        $response = $pdfGen->createPdfFile($pdf,'',$orderId,$pdfDestPath);
        $this->assertTrue(file_exists($response->notes));
        $this->assertRegExp("/(".$orderId."\.pdf){1,1}$/",$response->notes);
        unlink ($pdfDestPath."/".$orderId.".pdf");
        rmdir ($pdfDestPath);

    }

	// Preparing the Test 

	public function createApplication(){
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

     //
     // Migrates the database and set the mailer to 'pretend'.
     // This will cause the tests to run quickly.

    private function prepareForTests(){
		//---------------------------------------------------------------
		//Real data testing
		//---------------------------------------------------------------

    }

}