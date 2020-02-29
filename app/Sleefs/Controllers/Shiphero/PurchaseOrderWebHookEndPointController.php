<?php

namespace Sleefs\Controllers\Shiphero;

use App\Http\Controllers\Controller;
use \Google\Spreadsheet\DefaultServiceRequest;
use \Google\Spreadsheet\ServiceRequestFactory;
use \mdeschermeier\shiphero\Shiphero;
use \Sleefs\Helpers\ProductTypeGetter;

use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;

use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetGetWorkSheetIndex;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileLocker;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileUnLocker;


use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Models\Shiphero\PurchaseOrderUpdate;
use Sleefs\Models\Shiphero\PurchaseOrderUpdateItem;
use Sleefs\Helpers\Shiphero\POQtyTotalizer;


use Sleefs\Controllers\AutomaticProductPublisher;

use Sleefs\Helpers\ShopifyAPI\Shopify;
use Sleefs\Helpers\ShopifyAPI\RemoteProductGetterBySku;
use Sleefs\Helpers\Shopify\ProductGetterBySku;  
use Sleefs\Helpers\Shopify\ProductPublishValidatorByImage;
use Sleefs\Helpers\Shopify\ProductTaggerForNewResTag;
use Sleefs\Helpers\FindifyAPI\Findify;  

use Sleefs\Models\Monday\Pulse;
use Sleefs\Helpers\MondayApi\MondayApi;
use Sleefs\Helpers\Monday\MondayVendorValidator; 
use Sleefs\Helpers\Monday\MondayPulseNameExtractor;
use Sleefs\Helpers\Monday\MondayGroupChecker;
use Sleefs\Helpers\Monday\MondayFullPulseColumnGetter;


use \Sleefs\Helpers\Misc\Response;
use \PHPMailer\PHPMailer\PHPMailer;   


Class PurchaseOrderWebHookEndPointController extends Controller {


    private $mondayPulseColumnMap = array (
        'name' => 'name',//Pulse Name
        'title' => 'title6',//PO Title
        'vendor' => 'vendor2',//Vendor
        'created date' => 'created_date8',//Created Date
        'expected date' => 'expected_date3',//Expected Date
        'pay' => 'pay',//Pay
        'received' => 'received',//Received
        'total cost' => 'total_cost0',//Total Cost

    );


    private $mondayValidVendors = array('DX Sporting Goods','Good People Sports');
	

	public function __invoke(){



        /*
            0.  Se inicializan los objetos necesarios para gestionar la peticion que está dividida
                en 3 partes:

                1. Registro de los datos de la PO en el libro "POS" del spreadsheet
                2. Registro de los datos de la PO en el libro "Orders" del spreadsheet
                3. Registro en la DB local los datos de la presente actualización
                4. Registro de los datos de la PO en el libro "Qty-ProductType" del spreadsheet y registro de la orden en la DB
                5. Se publican los productos que no estén publicados en la tienda shopify
                6. Se publica/modifica en monday.com el estado de la PO entrante
        --->    7. Se genera la respuesta al servidor de shiphero //No tiene correspondencia en el array $debug
        
        */

        $debug = array(false,true,true,true,false,true);//Define que funciones se ejecutan y cuales no. - Produccion
        //$debug = array(false,false,true,true,true,true);//Define que funciones se ejecutan y cuales no. - Test


		$po = json_decode(file_get_contents('php://input'));

		//print_r(json_decode($entityBody));
		$clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
		$clogger->writeToLog ("Procesando PO: ".json_encode($po),"INFO");

		/* Genera la PO extendida */
		Shiphero::setKey('8c072f53ec41629ee14c35dd313a684514453f31');
        $poextended = Shiphero::getPO($po->purchase_order->po_id);
        $clogger->writeToLog ("Con PO Extendida: ".json_encode($poextended),"INFO");
		/* 
			Recupera la hoja de calculo para 
			determinar si es una PO nueva o vieja.
		*/

		$pathGoogleDriveApiKey = app_path('Sleefs/client_secret.json');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' .$pathGoogleDriveApiKey);

        $gclient = new \Google_Client;
        $gclient->useApplicationDefaultCredentials();
        $gclient->setApplicationName("Sleeves - Shiphero - Sheets v4");
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

        $spreadsheet = (new \Google\Spreadsheet\SpreadsheetService)
        ->getSpreadsheetFeed()
        ->getByTitle('Sleefs - Shiphero - Purchase Orders');//Production
        //->getByTitle('CP Sleefs - Shiphero - Purchase Orders');//Dev


        /*

            Determina si la hoja de cáculo está siendo modificada

        */

        $wsCtrlIndex = new GoogleSpreadsheetGetWorkSheetIndex();
        $wsCtrlLocker =  new GoogleSpreadsheetFileLocker();
        $wsCtrlUnLocker =  new GoogleSpreadsheetFileUnLocker();

        $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
        $index = $wsCtrlIndex->getWSIndex($worksheets,'Control');
        $worksheet = $worksheets[$index];
        $cellFeed = $worksheet->getCellFeed();
        $cell = $cellFeed->getCell(1,1);
        
        if ($cell->getContent()=='locked'){

            return response()->json(["code"=>204,"Message" => "Not available system"]);

        }
        /*

            1. Almacena los registros el libro "Line Items" del documento en google spreadsheets

        */

        //===============================================================
        //===============================================================
        // Se evita el registro sobre el primer libro de la hoja de cáculo
        // la hoja denominada "Line Items"
        // este cambio se efectua por @maomuriel el 2018-10-10
        //===============================================================
        //===============================================================

        if ($debug[0] == true){

            $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[0];
            $listFeed = $worksheet->getListFeed(); // Trae los registros con indice asociativo (nombre la columna)
            // @var ListEntry
            $alreadyAdded = false;
            $itemsRegistered = array();

            //Genera las actualizaciones
            foreach ($listFeed->getEntries() as $entry) {
               $record = $entry->getValues();
               if ($record['id'] == $po->purchase_order->po_id){

                    foreach ($poextended->po->results->items as $po_item){
                        //$product = $variant->product;
                        if ($record['sku'] == $po_item->sku){

                            $variant = Variant::where("sku","=",$po_item->sku)->first();
                            //var_dump($variant->product->product_type);

                       		// Actualiza los registros //


                       		//$record'id' => $po->purchase_order->po_id,
            	            $record['ordered'] = $po_item->quantity; 
                            $record['po'] = $poextended->po->results->po_number; 
                            $record['received'] = $po_item->quantity_received;
                            $record['pending'] = $po_item->quantity - $po_item->quantity_received;
                            $record['status'] = $po_item->fulfillment_status;
                            $record['total'] = $poextended->po->results->total_price;

                            if ($variant != null)
                                $record['type'] = $variant->product->product_type;
                            else
                                $record['type'] = 'ND';


                            //===============================================================
                            //===============================================================
                            // Se evita el registro sobre el primer libro de la hoja de cáculo
                            // la hoja denominada "Line Items"
                            // este cambio se efectua por @maomuriel el 2018-10-10
                            //===============================================================
                            //===============================================================
            	            $entry->update($record);
                            array_push($itemsRegistered,$po_item->sku);
                            break;

                        }

                    }
               }
            }
            //return false;
            //Genera los nuevos registros para el TAB: POS, y Genera la información de:
            // 
            // - Total Items
            // - Total recibidos
            //
            $orderTotalItemsReceived = 0;
            $orderTotalItems = 0;


            foreach ($poextended->po->results->items as $po_item){

                // Estos dos valores son utilizados en el siguiente paso
                $orderTotalItemsReceived += (0 + $po_item->quantity_received);
                $orderTotalItems += (0 + $po_item->quantity);


                $alreadyAdded = false;
                foreach($itemsRegistered as $itemRegistered){

                    if ($po_item->sku == $itemRegistered){
                        $alreadyAdded = true;
                        break;
                    }
                }

                if (!$alreadyAdded){

                    $variant = Variant::where("sku","=",$po_item->sku)->first();
                    if ($variant != null)
                        $typeToRecord = $variant->product->product_type;
                    else
                        $typeToRecord = '';
                    $listFeed->insert([

                        'id' => $po->purchase_order->po_id,
                        'po' => $poextended->po->results->po_number,
                        'sku' => $po_item->sku,
                        'status' => $po_item->fulfillment_status,
                        'ordered' => $po_item->quantity,
                        'received' => $po_item->quantity_received,
                        'pending' => $po_item->quantity - $po_item->quantity_received,
                        'total' => $poextended->po->results->total_price,
                        'type' => $typeToRecord,

                        ]);

                }

            }
        }
    





        /*

            2. Almacena el registro el libro "POs" (Orders) del documento en google spreadsheets

        */
    	

        if ($debug[1] == true){

            $worksheetOrders = $worksheets[1];
            $listFeedOrders = $worksheetOrders->getListFeed(); // Trae los registros con indice asociativo (nombre la columna)
            /** @var ListEntry */
            $alreadyAdded = false;
            $itemsRegistered = array();


            $orderTotalItemsReceived = 0;
            $orderTotalItems = 0;
            foreach ($poextended->po->results->items as $po_item){
                $orderTotalItemsReceived += (0 + $po_item->quantity_received);
                $orderTotalItems += (0 + $po_item->quantity);
            }


            //Genera las actualizaciones
            $operation = 'insert';
            $operationalEntry = '';
            foreach ($listFeedOrders->getEntries() as $entry) {
               $record = $entry->getValues();
               if ($record['id'] == $po->purchase_order->po_id){
                    $operationalEntry = $entry;
                    if ($poextended->po->results->fulfillment_status == 'canceled' || $poextended->po->results->fulfillment_status == 'cancelled'){
                        $operation = 'delete';
                    }
                    else{
                        $operation = 'update';   
                    }
                    break;
               }
            }


            switch($operation){

                case 'update':
                    $record = $operationalEntry->getValues();
                    $record['poname'] = htmlspecialchars($poextended->po->results->po_number); 
                    $record['status'] = $poextended->po->results->fulfillment_status;
                    $record['expecteddate'] = '';
                    $record['vendor'] = htmlspecialchars($poextended->po->results->vendor_name);
                    $record['totalcost'] = $poextended->po->results->total_price;
                    $record['totalitems'] = $orderTotalItems;
                    $record['itemsreceived'] = $orderTotalItemsReceived;
                    $record['pendingitems'] = $orderTotalItems - $orderTotalItemsReceived;
                    //Registra la actualización
                    $operationalEntry->update($record);
                    break;

                case 'insert':
                    $listFeedOrders->insert([
                        'id' => $poextended->po->results->po_id,
                        'poname' => htmlspecialchars($poextended->po->results->po_number),
                        'status' => $poextended->po->results->fulfillment_status,
                        'createddate' => $poextended->po->results->created_at,
                        'expecteddate' => '',
                        'vendor' => htmlspecialchars($poextended->po->results->vendor_name),
                        'totalcost' => $poextended->po->results->total_price,
                        'totalitems' => $orderTotalItems,
                        'itemsreceived' => $orderTotalItemsReceived,
                        'pendingitems' => $orderTotalItems - $orderTotalItemsReceived,
                        'paid' => 'no',
                    ]);
                    break;
                case 'delete':
                    $operationalEntry->delete();
                    break;
            }
        }

        
        
        /*

            3.  Registra la Orden en la DB, recalcula los valores por ProductType
                y registro los datos "Qty-ProductType"

        */



        $ctrlUpdates = array();
        if ($debug[2] == true){
            //var_dump($poextended);
            //echo "\n===============\n";
            //var_dump($po);
            $arrProductType = array();

            //3.1. Define si la orden ya ha sido registrada en la DB
            $poDb = PurchaseOrder::where('po_id','=',$po->purchase_order->po_id)->first();
            if ($poDb == null){//No ha sido resgistrada aún
                $poDb = new PurchaseOrder();
                $poDb->po_id = $po->purchase_order->po_id;
                $poDb->po_number = $poextended->po->results->po_number;
                if ($poextended->po->results->po_date != 'None'){
                    $poDb->po_date = $poextended->po->results->po_date;
                }
                $poDb->fulfillment_status = $poextended->po->results->fulfillment_status;
                if (isset($poextended->po->results->shipping_price))
                    $poDb->sh_cost = $poextended->po->results->shipping_price;
                $poDb->save();

                //3.2. Se registran los PO Items
                for ($i = 0; $i < count($po->purchase_order->line_items);$i++){

                    $itemExt = $poextended->po->results->items[$i];
                    $itemShort = $po->purchase_order->line_items[$i];
                    $variant = Variant::where('sku','=',$itemExt->sku)->first();
                    $prdTypeItem = 'nd';

                    if ($variant!=null && is_object($variant)){
                        $prdTypeItem = $variant->product->product_type;
                    }

                    if (!isset($arrProductType[$prdTypeItem])){
                        $arrProductType[$prdTypeItem] = 1;
                    }
                    $itm = new PurchaseOrderItem();
                    $itm->idpo = $poDb->id;
                    $itm->sku = $itemExt->sku;
                    $itm->shid = $itemShort->id;
                    $itm->quantity = $itemExt->quantity;
                    $itm->quantity_received = $itemExt->quantity_received;
                    $itm->name = $itemExt->product_name;
                    $itm->idmd5 = md5($itemExt->sku.'-'.$poDb->po_id);
                    $itm->product_type = $prdTypeItem;
                    $itm->qty_pending = ((int)$itemExt->quantity - (int)$itemExt->quantity_received);
                    $itm->price = $itemExt->price;
                    $itm->save();
                    //Registra el dato de actualización
                    $ctrlUpdates[$itemExt->sku] = $itemExt->quantity_received;
                }
            }
            else {//Ya la orden existe en el sistema, se actualizan los registros

                $poDb->po_number = $poextended->po->results->po_number;
                if ($poextended->po->results->po_date != 'None'){
                    $poDb->po_date = $poextended->po->results->po_date;
                }
                $poDb->fulfillment_status = $poextended->po->results->fulfillment_status;
                if (isset($poextended->po->results->shipping_price))
                    $poDb->sh_cost = $poextended->po->results->shipping_price;
                $poDb->save();

                for ($i = 0; $i < count($po->purchase_order->line_items);$i++){

                    $itemShort = $po->purchase_order->line_items[$i];
                    //$itemExt = $poextended->po->results->items[$i];
                    foreach ($poextended->po->results->items as $tmpItem){
                        if ($tmpItem->sku == $itemShort->sku){
                            $itemExt = $tmpItem;
                            break;
                        }
                    }
                    
                    $itm = PurchaseOrderItem::where('idmd5','=',md5($itemExt->sku.'-'.$poDb->po_id))->first();
                    if ($itm == null){

                        $variant = Variant::where('sku','=',$itemExt->sku)->first();
                        $prdTypeItem = 'nd';


                        if ($variant!=null && is_object($variant)){
                            $prdTypeItem = $variant->product->product_type;
                        }

                        $itm = new PurchaseOrderItem();
                        $itm->idpo = $poDb->id;
                        $itm->sku = $itemExt->sku;
                        $itm->shid = $itemShort->id;
                        $itm->quantity = $itemExt->quantity;
                        $itm->quantity_received = $itemExt->quantity_received;
                        $itm->name = $itemExt->product_name;
                        $itm->idmd5 = md5($itemExt->sku.'-'.$poDb->po_id);
                        $itm->product_type = $prdTypeItem;
                        $itm->qty_pending = ((int)$itemExt->quantity - (int)$itemExt->quantity_received);
                        $itm->price = $itemExt->price;
                        $itm->save();
                        //Registra el dato de actualización
                        $ctrlUpdates[$itemExt->sku] = $itemExt->quantity_received;
                    }
                    else{

                        //Registra el dato de actualización
                        $ctrlUpdates[$itemExt->sku] = ($itemExt->quantity_received - $itm->quantity_received);

                        $variant = Variant::where('sku','=',$itemExt->sku)->first();
                        $prdTypeItem = 'nd';
                        if ($variant!=null && is_object($variant)){
                            $prdTypeItem = $variant->product->product_type;
                        }
                        $itm->quantity = $itemExt->quantity;
                        $itm->quantity_received = $itemExt->quantity_received;
                        $itm->name = $itemExt->product_name;
                        $itm->product_type = $prdTypeItem;
                        $itm->qty_pending = ((int)$itemExt->quantity - (int)$itemExt->quantity_received);
                        $itm->save();
                    }

                    if (!isset($arrProductType[$prdTypeItem])){
                        $arrProductType[$prdTypeItem] = 1;
                    }

                }
            }


            // 3.3 Registra en el archivo de hoja de cálculo los nuevos valores para los ProductType
        
            //Define los valores desde la DB
            $arrPrdTypeKeys = array_keys($arrProductType);
            foreach ($arrPrdTypeKeys as $prdType){

                $arrProductType[$prdType] = \DB::table('sh_purchaseorder_items')->select(\DB::raw('sum(qty_pending) as total'))->where('product_type','=',$prdType)->first()->total;

            }


            //Registra propiamente dicho en la hoja de calculo


            /*
            //===============================================================
            //===============================================================
            // Se evita el registro sobre el primer libro de la hoja de cáculo
            // la hoja denominada "Line Items"
            // este cambio se efectua por @maomuriel el 2018-10-10
            //===============================================================
            //===============================================================


            $worksheetOrders = $worksheets[2];
            $listFeedOrders = $worksheetOrders->getListFeed(); // Trae los registros con indice asociativo (nombre la columna)
                
            foreach ($arrProductType as $key=>$val){

                $alreadyAdded = false;
                //Genera las actualizaciones
                foreach ($listFeedOrders->getEntries() as $entry) {
                   $record = $entry->getValues();
                   if ($record['type'] == $key){

                        $record['qty'] = $val;
                        $entry->update($record);
                        $alreadyAdded = true;
                        break;
                   }
                }
                //Genera el nuevo registro
                if (!$alreadyAdded){
                $listFeedOrders->insert([
                    'type' => $key,
                    'qty' => $val,
                    ]);
                }
            }

            */

        }

        /*
        $resUnLock = $wsCtrlUnLocker->unLockFile($spreadsheet,$index); 
        return "";
        */


        /*

            4.  Genera la información necesaria para la impresión de barcodes
                de las Ordenes de compra por fecha

        */

        //return response()->json($ctrlUpdates);
        if ($debug[3] == true){

            //Registra la PO Update
            $poUpdate = new PurchaseOrderUpdate();
            $poUpdate->idpo = $poDb->id;
            $poUpdate->save();

            //Registra los ITEMS de la PO Update
            $items = PurchaseOrderItem::whereRaw("idpo='".$poDb->id."'")->get();
            foreach ($po->purchase_order->line_items as $itemRaw){
                foreach ($items as $item){

                    if ($item->shid == $itemRaw->id){
                        //Registra el objeto PurchaseOrderUpdateItem
                        if (isset($ctrlUpdates[$itemRaw->sku])){
                            if (((int)($ctrlUpdates[$itemRaw->sku])) > 0){
                                $poUpdateItem = new PurchaseOrderUpdateItem();
                                $poUpdateItem->idpoupdate = $poUpdate->id;
                                $poUpdateItem->idpoitem = $item->id;
                                $poUpdateItem->quantity = $ctrlUpdates[$itemRaw->sku];
                                $poUpdateItem->qty_before = ( $itemRaw->quantity_received - $ctrlUpdates[$itemRaw->sku]);
                                $poUpdateItem->sku = $itemRaw->sku;
                                $poUpdateItem->save();
                            }
                            break;
                        }
                        else{
                            $clogger->writeToLog ("No está definido un valos para ".$itemRaw->sku,"WARNING");
                        }
                    }
                }
            }
        }
        

        //return response()->json(['ctrlUpdates'=>$ctrlUpdates,'poextended'=>$poextended,'po'=>$po]);


        /*

            5. Se publican los productos que no estén publicados en la tienda shopify

        */


        if ($debug[4] == true){



            //print_r($po);
            //print_r($poextended);
            //return false;
            

            $shopifyApi = new Shopify(env('SHPFY_APIKEY'),env('SHPFY_APIPWD'),env('SHPFY_BASEURL'));
            $publishValidatorByImage = new ProductPublishValidatorByImage();
            $tagger = new ProductTaggerForNewResTag();
            $findifyApi = new Findify(env('FINDIFY_ENDPOINT'));
            $remoteShopifyProductGetter = new RemoteProductGetterBySku();
            $publisher = new AutomaticProductPublisher();
            

            $htmlEmailBody = '';

            $actualIdProduct = '';
            $qtyPublishedProducts = 0;
            foreach($poextended->po->results->items as $shItem){
                if ($shItem->quantity_received > 0){//Solo se publican los productos que tengan ingreso al inventario

                    $localProductGetter = new ProductGetterBySku();
                    $localProduct = new Product();
                    $localProduct = $localProductGetter->getProduct($shItem->sku,$localProduct);
                    $shopifyProduct = $remoteShopifyProductGetter->getRemoteProductBySku($shItem->sku,$shopifyApi);
                    if ($shopifyProduct){
                        if ($shopifyProduct->published_at==null || $shopifyProduct->published_at=='' || $shopifyProduct->published_at==' '){
                            if ($shopifyProduct->id != $actualIdProduct){
                                $qtyPublishedProducts++;
                                $clogger->writeToLog ("Publicando el producto: ".json_encode($shopifyProduct),"INFO");
                                //Publica o al menos intenta publicación
                                $publishingResults = $publisher->publishProduct($shopifyProduct,$publishValidatorByImage,$shopifyApi,$tagger,$findifyApi);
                                $htmlEmailBody .= "\n".$qtyPublishedProducts.". ".$shopifyProduct->title." (".$shopifyProduct->id.")<br />\n";
                                if ($publishingResults->value == true){
                                    //Publicó correctamente
                                    if (preg_match("/(^NEW[0-9]{6,6}|^RES[0-9]{6,6})/",$publishingResults->notes)){
                                        $htmlEmailBody .= "El producto se ha publicado con el tag: ".$publishingResults->notes."<br /><br />\n\n";
                                    }
                                }
                                else{
                                    //No publicó
                                    if ($publishingResults->notes == 'No images'){
                                        $htmlEmailBody .= "This product (https://".env('SHPFY_BASEURL')."/products/".$shopifyProduct->id.") doesn't have at least one related image.<br /><br />\n\n";
                                    }
                                }
                                $actualIdProduct = $shopifyProduct->id;
                            }
                        }
                    }
                }
            }
            if (!($htmlEmailBody == '')){

                $this->sendPublisingReport("Next are the results of publishing products of PO No.".$poextended->po->results->po_number." to sleefs.com store:<br /><br />\n\n".$htmlEmailBody,"Publishing items report for PO: ".$poextended->po->results->po_number);

            }
        } 
        /*
            
            6.  Despacha la adición/modificación de la PO hacía la plataforma
                de monday.com
        */

        if ($debug[5] == true){

            //6.1. Verifica si la orden la PO es elegible para registro en monday
            //$this->mondayValidVendors = array('DX Sporting Goods','Good People Sports');
            $vendorValidator = new MondayVendorValidator($this->mondayValidVendors);
            $pulseNameExtractor = new MondayPulseNameExtractor();
            $mondayApi = new MondayApi(env('MONDAY_BASEURL'),env('MONDAY_APIKEY'));


            if ($vendorValidator->validateVendor(ucwords(trim($poextended->po->results->vendor_name)))){
                //6.2 Verifica si la orden ya tiene un objeto tipo pulse creado
                $pulseName = $pulseNameExtractor->extractPulseName($poextended->po->results->po_number);
                $pulses = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
                $pulse = '';
                if($pulses->count() > 0){
                    $pulse = $pulses->get(0);
                }else{

                    //Si no lo tiene genera uno
                    $pulse = new Pulse();
                    if (isset($poDb) && isset($poDb->id)){
                        $pulse->idpo = $poDb->id;
                    }
                    else{
                        $poDb = PurchaseOrder::where('po_id','=',$po->purchase_order->po_id)->first();
                        $pulse->idpo = $poDb->id;
                    }
                    $pulse->idmonday = '';
                    $pulse->name = $pulseName;
                    $pulse->mon_board = env('MONDAY_BOARD');
                    $pulse->mon_group = '';
                }
                //=======================================================
                //6.3   Verifica si ya existe un pulse en el tablero con 
                //      el mismo nombre.
                $fullPulse = $mondayApi->getFullPulse($pulse,env('MONDAY_BOARD'));
                if ($fullPulse == null){

                    //6.4   Recupera el grupo al que pertenece la PO
                    $groupChecker = new MondayGroupChecker();
                    $group = $groupChecker->getGroup($pulse->name,env('MONDAY_BOARD'),$mondayApi);
                    if ($group==null){
                        //6.5   Genera un nuevo grupo
                        $groupTitle = $groupChecker->getCorrectGroupName ($pulse->name);
                        $data = array(
                            'board_id' => env('MONDAY_BOARD'),
                            'title' => $groupTitle,
                        );
                        $group = $mondayApi->addGroupToBoard(env('MONDAY_BOARD'),$data);
                    }



                    $pulseData = array(
                        'pulse[name]' => $pulse->name,
                        'board_id' => env('MONDAY_BOARD'),
                        'user_id' => env('MONDAY_USER'),
                        'group_id' => $group->id,
                    );
                    $newPulse = $mondayApi->createPulse(env('MONDAY_BOARD'),$pulseData);
                    $fullPulse = $mondayApi->getFullPulse($pulse,env('MONDAY_BOARD'));
                }
                if ($pulse->idmonday=='' || $pulse->mon_board=='' || $pulse->mon_group==''){
                    $pulse->idmonday = $fullPulse->pulse->id;
                    $pulse->mon_board = env('MONDAY_BOARD');
                    $pulse->mon_group = $fullPulse->board_meta->group_id;
                    $pulse->save();
                }
                //======================================================
                //6.6   Genera las actualizaciones de los campos
                //======================================================
                $pulseGetterValue = new MondayFullPulseColumnGetter();//Recuperador de los valores de las columnas en el $fullPulse
                //6.6.1 Verifica el title del pulse
                $pulseTitleCandidate = $poextended->po->results->po_number;
                $pulseTitleCandidate = preg_replace("/^".$pulse->name."/","",$pulseTitleCandidate);
                $pulseTitleCandidate = trim($pulseTitleCandidate);                
                $pulseTitle = $pulseGetterValue->getValue($this->mondayPulseColumnMap['title'],$fullPulse);

                if ($pulseTitle != $pulseTitleCandidate || $pulseTitle == ''){
                    $dataPulse = array(
                        'text' => $pulseTitleCandidate,
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['title'],'text',$dataPulse);
                }
                //6.6.2 Verifica el vendor del pulse
                $pulseVendorCandidate = $poextended->po->results->vendor_name;              
                $pulseVendor = $pulseGetterValue->getValue($this->mondayPulseColumnMap['vendor'],$fullPulse);
                if ($pulseVendor != $pulseVendorCandidate || $pulseVendor == ''){
                    $dataPulse = array(
                        'text' => $pulseVendorCandidate,
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['vendor'],'text',$dataPulse);
                }
                //6.6.3 Verifica el created date del pulse
                $pulseCreatedAtCandidate = substr($poextended->po->results->created_at,0,10);
                $pulseCreatedAt = $pulseGetterValue->getValue($this->mondayPulseColumnMap['created date'],$fullPulse);
                if ($pulseCreatedAt != $pulseCreatedAtCandidate || $pulseCreatedAt == ''){
                    $dataPulse = array(
                        'date_str' => $pulseCreatedAtCandidate,
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['created date'],'date',$dataPulse);
                }
                //6.6.4 Verifica el expected date del pulse
                $pulseExpectedAtCandidate = substr($poextended->po->results->po_date,0,10);
                $pulseExpectedAt = $pulseGetterValue->getValue($this->mondayPulseColumnMap['expected date'],$fullPulse);
                if ($pulseExpectedAt != $pulseExpectedAtCandidate || $pulseExpectedAt == ''){
                    $dataPulse = array(
                        'date_str' => $pulseExpectedAtCandidate,
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['expected date'],'date',$dataPulse);
                }
                //6.6.5 Verifica el received del pulse
                $poTotalizer = new POQtyTotalizer();
                $totalQtyPoItems = $poTotalizer->getTotalItems($pulse->idpo,'total');
                $totalQtyPoItemsReceived = $poTotalizer->getTotalItems($pulse->idpo,'received');
                $pulseStatusIndex = $pulseGetterValue->getValue($this->mondayPulseColumnMap['received'],$fullPulse);


                if ($totalQtyPoItemsReceived == 0 ){
                    //No se ha recibido ningun item
                    $pulseStatusIndexCandidate = 2;//$index=2, quiere decir color rojo, no se ha recibido nada
                }
                elseif($totalQtyPoItemsReceived > 0 && ($totalQtyPoItemsReceived < $totalQtyPoItems)){
                    //Se ha recibido pero faltan
                    $pulseStatusIndexCandidate = 9;//$index=9, quiere decir color amarillo, recepción parcial de productos
                }
                elseif($totalQtyPoItemsReceived > 0 && ($totalQtyPoItemsReceived == $totalQtyPoItems)){
                    //PO completa
                    $pulseStatusIndexCandidate = 1;//$index=1, quiere decir color verde, recepción completa de productos
                }
                else{
                    //Definicion indeterminada
                    $pulseStatusIndexCandidate = 5;//$index=5, quiere decir color gris, estatus no determinado
                }
                
                if ($pulseStatusIndex != $pulseStatusIndexCandidate || $pulseStatusIndex == ''){
                    $dataPulse = array(
                        'color_index' => $pulseStatusIndexCandidate,
                        'update_id' => "SLEEFS-APP".date("Y-m-dH:i:s"),
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['received'],'status',$dataPulse);
                }

                //6.6.6. Verifica el total de la orden
                $pulseTotalCostCandidate = $poextended->po->results->total_price;              
                $pulseTotalCost = $pulseGetterValue->getValue($this->mondayPulseColumnMap['total cost'],$fullPulse);
                if ($pulseTotalCost != $pulseTotalCostCandidate || $pulseTotalCost == '' || $pulseTotalCost == 0.0){
                    $dataPulse = array(
                        'value' => $pulseTotalCostCandidate,
                    );
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['total cost'],'numeric',$dataPulse);
                }

                
            }


        }

        /*

            7.  Genera la respuesta al servidor de shiphero
                y bloquea la hoja de cáculo

        */

        //Realiza el desbloqueo del documento
        $resUnLock = $wsCtrlUnLocker->unLockFile($spreadsheet,$index);
		return response()->json(["code"=>200,"Message" => "Success"]);



	}




    private function sendPublisingReport($textEmail,$subject){

        $response = new Response();
        try{
            $text             = $textEmail."<br /><br />\n\n";
            $mail             = new PHPMailer();
            $mail->isSMTP();
            $mail->SMTPDebug  = false; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth   = true; // authentication enabled
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION'); // secure transfer enabled REQUIRED for Gmail
            $mail->Host       = getenv('MAIL_HOST');
            $mail->Port       = getenv('MAIL_PORT'); // or 587
            $mail->IsHTML(true);
            $mail->Username = getenv('MAIL_USERNAME');
            $mail->Password = getenv('MAIL_PASSWORD');
            $mail->SetFrom("mauricio.muriel@sientifica.com", 'Mauricio Muriel');
            $mail->Subject = $subject;
            $mail->Body    = $text;
            //$mail->AddAddress("mauricio.muriel@calitek.net", "Mauricio Muriel");
            $mail->AddAddress("jschuster@sleefs.com", "Jaime Schuster");
            $mail->Send();
            $response->value = true;
        }
        catch(\Exception $e){
            $response->value = false;
            $response->status = false;
            $response->notes = $mail->ErrorInfo;
            return $response;
        }

        return $response;
    }
	

}