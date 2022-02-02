<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;

use Sleefs\Helpers\Shiphero\ShipheroFulfillmentStatusSyncedDataChecker;
use Sleefs\Helpers\Shiphero\ShipheroToLocalPODataSyncer;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;

class ShipheroPOsSyncer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sleefs:shipheroPOsSyncer {--pos=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It syncs local and remote (shiphero) PO data';

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
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.pos-syncer.log");
        $pos = $this->option('pos');
        if (isset($pos) && count($pos) > 0){
            //print_r($pos);

            $strQuery = '';
            foreach ($pos as $poId){
                $strQuery .= " (po_id = '".$poId."' || po_id_legacy = '".$poId."') || ";
            }

            $strQuery = preg_replace("/\ \|\|\ $/","",$strQuery);
            echo "\n".$strQuery."\n";

            $localPos = PurchaseOrder::whereRaw($strQuery)->get();
        }
        else{
            echo "No se ha indicado PO alguna...\n";
            $localPos = PurchaseOrder::whereRaw("fulfillment_status='pending'")->get();
        }

        /*



        */

        $fulfillmentStatusChecker = new ShipheroFulfillmentStatusSyncedDataChecker();
        $localPoDataToRemotePoDataSyncer = new ShipheroToLocalPODataSyncer();

        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));


        foreach($localPos as $localPo){
            //1. Get remote PO data
            //$shipHeroApi->getExtendedPO();
            $gqlQuery = '';


            //if ($localPo->po_id_token != '' && $localPo->po_id_token != null) {
            $idtoken = '';
            $poNumber = '';
            $cstmQueryStr = '';
            //--------------------------------------------------
            if ($localPo->po_id_legacy > 0){ 
                //$remoteRawPOData = $shipHeroApi->getExtendedPOCustomQuery('po_number:"1904-25 remake elite shorts"');
                //echo "ID Token:".$localPo->po_id_token."|ID Legacy:".$localPo->po_id_legacy."|PO Number:".$localPo->po_number."\n";
                $idtoken = $localPo->po_id_legacy;
            }
            if (!preg_match("/^\ {1,1}$/",$localPo->po_id_token)){ 
                //$remoteRawPOData = $shipHeroApi->getExtendedPOCustomQuery('po_number:"1904-25 remake elite shorts"');
                //echo "ID Token:".$localPo->po_id_token."|ID Legacy:".$localPo->po_id_legacy."|PO Number:".$localPo->po_number."\n";
                $idtoken = $localPo->po_id_token;
            }

            if ($idtoken != ''){
                $cstmQueryStr .= 'id:"'.$idtoken.'",';
            }

            //--------------------------------------------------
            if (!(preg_match("/^\ {1,1}$/",$localPo->po_number) || $localPo->po_number=='' || $localPo->po_number == null)){
                $poNumber = $localPo->po_number;
            }

            
            if ($poNumber != '' && $cstmQueryStr==''){
                $cstmQueryStr .= 'po_number:"'.$poNumber.'",';
            }

            $cstmQueryStr = preg_replace("/\,{1,1}$/","",$cstmQueryStr);

            echo $cstmQueryStr."\n";
            if ($cstmQueryStr == ''){
                $clogger->writeToLog ("La PO ".$localPo->id." no tiene definidos identificadores remotos para vincular","WARNING");
                continue;
            }


            $remotePoData = $shipHeroApi->getExtendedPOCustomQuery($cstmQueryStr);
            sleep(10);
            if (!$fulfillmentStatusChecker->validateSyncedData($localPo,$remotePoData->data->purchase_order->data)){

                //It must Syncs
                list($error,$localPo) = $localPoDataToRemotePoDataSyncer->syncData($localPo,$remotePoData->data->purchase_order->data);
                if ($error){

                    $clogger->writeToLog ("Error sincronizando los datos de la orden. ".$localPo,"ERROR");

                }
                else{
                    $clogger->writeToLog ("Se ha sincronizado la orden. ".$localPo->id." (PO Number: ".$localPo->po_number.")","INFO");
                }
            }


            

        }
        $clogger->writeToLog ("Sincronizando POs","INFO");
    }


    private function syncPo($localPo){



    }
}
