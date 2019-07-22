<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Google\Spreadsheet\DefaultServiceRequest;
use \Google\Spreadsheet\ServiceRequestFactory;

class GoogleSpreadSheetApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'GoogleSpreadSheetApi:getSpreadSheet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It gets an a google spreadsheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        echo "Google Spreadsheet API - Get Spreadsheet\n";

        $pathGoogleDriveApiKey = app_path('Sleefs/client_secret.json');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$pathGoogleDriveApiKey);

        $gclient = new \Google_Client;
        $gclient->useApplicationDefaultCredentials();
        $gclient->setApplicationName("Sleeves - Shiphero - Sheets v4 - Test");
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
        //var_dump($ssfeed);
        echo "\n=========\n\n";
        foreach ($ssfeed->getEntries() as $spreadSheet){

            echo "\n";
            //var_dump($spreadSheet);

        }

        

        $spreadsheet = (new \Google\Spreadsheet\SpreadsheetService)
        ->getSpreadsheetFeed()
        //->getByTitle('Sleefs - Shiphero - Google Spreadsheet');
        //->getById('https://docs.google.com/spreadsheets/d/17IiATPBE1GAIxDW-3v4xSG3yr0QOhxr1bQCordZJqds/');
        ->getById('17IiATPBE1GAIxDW-3v4xSG3yr0QOhxr1bQCordZJqds');
        //var_dump($spreadsheet);
        
        // Get the first worksheet (tab)
        $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
        print_r($worksheets);
        $worksheet = $worksheets[1];
        return false;
        $listFeed = $worksheet->getListFeed(); // Trae los registros con indice asociativo (nombre la columna)
        /** @var ListEntry */
        
        foreach ($listFeed->getEntries() as $entry) {
           $record = $entry->getValues();
           var_dump($record);
        }
        

        $cellFeed = $worksheet->getCellFeed(); // Indices númericos
        $arrCellFeed = $cellFeed->toArray();
        //print_r($arrCellFeed);
    
        $listFeed->insert([

            'columna1' => '26',
            'columna2' => 'Tulio',
            'columna3' => '134',

        ]);
        /*
        $serviceRequest = new \Google\Spreadsheet\DefaultServiceRequest('AIzaSyBTeVrDKIdjng5bMKgbWwVlLBReNsMOYVw',"");
        var_dump($serviceRequest);
        
        ServiceRequestFactory::setInstance($serviceRequest);
        
        $spreadsheetService = new \Google\Spreadsheet\SpreadsheetService();
        var_dump($spreadsheetService);
        
        $worksheetFeed = $spreadsheetService->getPublicSpreadsheet("2PACX-1vQaJGE2vg9C1qLyt5TxBAQeMkhXqHMnQpnFUslMFPy6oCIDvmlyCJzHKVRJgmc33OvO0VKw5M22_c-g");
        var_dump($worksheetFeed);
        */
        
    }
}
