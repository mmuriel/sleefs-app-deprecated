<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\Shiphero\ShipheroVendorsDiffChecker;
use Sleefs\Models\Shiphero\Vendor;
use Sleefs\Helpers\CustomLogger;


class SyncShipheroVendors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShipheroAPI:syncvendors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command syncs the vendor list from shiphero.com to the local DB vendors record';

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
        $clogger = new CustomLogger("sleefs.log");
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql',array("Authorization: Bearer ".env('SHIPHERO_ACCESSTOKEN')));


        //It gets the the 
        $gqlQuery = array("query" => '{vendors{data(first:100){edges{node{name,legacy_id,id,email,account_number}}}}}');
        $gqlVendorsRequest = $gqlClient->query($gqlQuery,array("Content-type: application/json"));

        $shipheroVendorChecker = new ShipheroVendorsDiffChecker();
        $vendorsDiff = $shipheroVendorChecker->checkDiff($gqlVendorsRequest->data->vendors->data->edges);
        foreach($vendorsDiff as $apiVendor){
          $localVendor = new Vendor();
          $localVendor->idsp = $apiVendor->node->id;
          $localVendor->name = $apiVendor->node->name;
          $localVendor->legacy_idsp = $apiVendor->node->legacy_id;
          if ($apiVendor->node->email == null || $apiVendor->node->email == ''){
            $apiVendor->node->email = 'xxxxxxx@xxxxx.com';
          }
          $localVendor->email = $apiVendor->node->email;
          $localVendor->save();
          //============================================
          $clogger->writeToLog ("Se crea un nuevo vendor que existente en shiphero, vendor name: ".$localVendor->name,"INFO");
        }
    }
}
