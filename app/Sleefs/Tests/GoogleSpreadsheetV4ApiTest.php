<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use App\Http\Controllers\Controller;
use \Google\Spreadsheet\DefaultServiceRequest;
use \Google\Spreadsheet\ServiceRequestFactory;


use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileLocker;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileUnLocker;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetGetWorkSheetIndex;

class GoogleSpreadsheetV4ApiTest extends TestCase {


	public $spreadsheet;

	public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();
    }
 
    /*
    public function testGetCtrlWorkSheet(){


    	$wsctrl = new GoogleSpreadsheetGetWorkSheetIndex();
    	$worksheets = $this->spreadsheet->getWorksheetFeed()->getEntries();
    	$index1 = $wsctrl->getWSIndex($worksheets,'Control');
    	$index2 = $wsctrl->getWSIndex($worksheets,'Control MMMM');
    	$this->assertEquals(3,$index1);
    	$this->assertEquals(false,$index2);

    }


    public function testLockSpreadsheetFile(){

    	
    	$wsCtrlIndex = new GoogleSpreadsheetGetWorkSheetIndex();
    	$wsCtrlLocker =  new GoogleSpreadsheetFileLocker();
    	
    	//Genera el index del "libro" (worksheet) que tiene la celda de control.
    	$worksheets = $this->spreadsheet->getWorksheetFeed()->getEntries();
    	$index = $wsCtrlIndex->getWSIndex($worksheets,'Control');

    	//Realiza el bloqueo del documento
    	$resLock1 = $wsCtrlLocker->lockFile($this->spreadsheet,$index);
    	$resLock2 = $wsCtrlLocker->lockFile($this->spreadsheet,10);

    	//Verifica que la peticion no delvuelva error en condiciones normales y que devuelva error cuando se induce a eso
    	$this->assertEquals(true,$resLock1);
    	$this->assertEquals(false,$resLock2);

    	//Verifica que el contenido de la celda A1 sea = locked
		$worksheet = $worksheets[$index];
		$cellFeed = $worksheet->getCellFeed();
		$cell = $cellFeed->getCell(1,1);
		$this->assertEquals('locked',$cell->getContent());		
    
    }


    public function testUnlockSpreadsheetFile(){

    	$wsCtrlIndex = new GoogleSpreadsheetGetWorkSheetIndex();
    	$wsCtrlLocker =  new GoogleSpreadsheetFileUnLocker();
    	
    	//Genera el index del "libro" (worksheet) que tiene la celda de control.
    	$worksheets = $this->spreadsheet->getWorksheetFeed()->getEntries();
    	$index = $wsCtrlIndex->getWSIndex($worksheets,'Control');

    	//Realiza el bloqueo del documento
    	$resLock1 = $wsCtrlLocker->unLockFile($this->spreadsheet,$index);
    	$resLock2 = $wsCtrlLocker->unLockFile($this->spreadsheet,10);

    	$this->assertEquals(true,$resLock1);
    	$this->assertEquals(false,$resLock2);

    	//Verifica que el contenido de la celda A1 = open
		$worksheet = $worksheets[$index];
		$cellFeed = $worksheet->getCellFeed();
		$cell = $cellFeed->getCell(1,1);
		$this->assertEquals('open',$cell->getContent());		
    }


    */
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

     	// \Artisan::call('migrate');
     	$pathGoogleDriveApiKey = app_path('Sleefs/client_secret.json');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$pathGoogleDriveApiKey);

        $gclient = new \Google_Client;
        $gclient->useApplicationDefaultCredentials();
        $gclient->setApplicationName("Sleeves - Shiphero - Sheets v4 - DEV");
        $gclient->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);
        if ($gclient->isAccessTokenExpired()) {
            $gclient->refreshTokenWithAssertion();
        }
        $accessToken = $gclient->fetchAccessTokenWithAssertion()["access_token"];
        ServiceRequestFactory::setInstance(
            new DefaultServiceRequest($accessToken)
        );

        $spreadSheetService = new \Google\Spreadsheet\SpreadsheetService();
        $ssfeed = $spreadSheetService->getSpreadsheetFeed();

        $this->spreadsheet = (new \Google\Spreadsheet\SpreadsheetService)
        ->getSpreadsheetFeed()
        ->getByTitle('Sleefs - Shiphero - Purchase Orders - DEV');
    }

}