<?php

namespace Sleefs\Controllers\Shiphero;

use App\Http\Controllers\Controller;
use \Google\Spreadsheet\DefaultServiceRequest;
use \Google\Spreadsheet\ServiceRequestFactory;
use \mdeschermeier\shiphero\Shiphero;
use \Sleefs\Helpers\ProductTypeGetter;

use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;

use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;

use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetGetWorkSheetIndex;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileLocker;
use Sleefs\Helpers\Google\SpreadSheets\GoogleSpreadsheetFileUnLocker;


use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Models\Shiphero\PurchaseOrderUpdate;
use Sleefs\Models\Shiphero\PurchaseOrderUpdateItem;
use Sleefs\Models\Shiphero\Vendor;
use Sleefs\Helpers\Shiphero\POQtyTotalizer;


use Sleefs\Controllers\AutomaticProductPublisher;

use Sleefs\Helpers\ShopifyAPI\Shopify;
use Sleefs\Helpers\ShopifyAPI\RemoteProductGetterBySku;
use Sleefs\Helpers\Shopify\ProductGetterBySku;  
use Sleefs\Helpers\Shopify\ProductPublishValidatorByImage;
use Sleefs\Helpers\Shopify\ProductTaggerForNewResTag;
use Sleefs\Helpers\FindifyAPI\Findify;  

use Sleefs\Models\Monday\Pulse;
use Sleefs\Helpers\MondayApi\MondayGqlApi;
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

        //$debug = array(false,true,true,true,false,true);//Define que funciones se ejecutan y cuales no. - Produccion
        $debug = array(false,false,true,true,false,true);//Define que funciones se ejecutan y cuales no. - Test


		$po = json_decode(file_get_contents('php://input'));

		//print_r(json_decode($entityBody));
		$clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
		$clogger->writeToLog ("Procesando PO: ".json_encode($po),"INFO");

		/* Genera la PO extendida */
		//Shiphero::setKey('8c072f53ec41629ee14c35dd313a684514453f31');
        //$poextended = Shiphero::getPO($po->purchase_order->po_id);
        $gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
        $shipheroGqlApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));

        $poextended = $shipheroGqlApi->getExtendedPO($po->purchase_order->id);
        $poextended = $poextended->data->purchase_order->data;
        $poextended->line_items = $poextended->line_items->edges;
        if (isset($poextended->line_items[0])){
            if (isset($poextended->line_items[0]->node->vendor->name) && isset($poextended->line_items[0]->node->vendor->id)){
                $poextended->vendor_name = $poextended->line_items[0]->node->vendor->name;
                $poextended->vendor_id = $poextended->line_items[0]->node->vendor->id;
            }
            else {
                $poextended->vendor_name = "ND";
                $poextended->vendor_id = "ND";   
            }
        }
        else
        {
            $clogger->writeToLog ("ORDER: La PO ".$po->purchase_order->id." no incluye productos (line items), no se procesa hasta que se definan estos elementos en sus registros","ERROR");
            return response()->json(["code"=>200,"Message" => "Success"]);
        }




        $poextended->po_date = date("Y-m-d H:i:s",strtotime($poextended->po_date));
        $poextended->created_at = date("Y-m-d H:i:s",strtotime($poextended->created_at));
        $clogger->writeToLog ("Con PO Extendida: ".json_encode($poextended),"INFO");
		/* 
			Recupera la hoja de calculo para 
			determinar si es una PO nueva o vieja.
		
            Made by: @maomuriel
            Note: Se elimina la vinculación con Google Sheets por cambio de API de autenticación
            y compljidad en la adaptación, pero sobre todo, porque ya no se necesita más.
            Fecha: 2021-10-03

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
        ->getByTitle(env('GOOGLE_SPREADSHEET_DOC'));


        //Determina si la hoja de cáculo está siendo modificada

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

        */
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

            $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();// Selecciona el objeto que toma todos los libros de la hoja de cálculo.
            $worksheet = $worksheets[0];//Toma el primer libro de la hoja de cáculo.
            $listFeed = $worksheet->getListFeed(); // Trae los registros con indice asociativo (nombre la columna)
            // @var ListEntry
            $alreadyAdded = false;
            $itemsRegistered = array();

            //Genera las actualizaciones
            foreach ($listFeed->getEntries() as $entry) {
               $record = $entry->getValues();
               if ($record['id'] == $po->purchase_order->po_id){

                    foreach ($poextended->line_items as $po_item){
                        //$product = $variant->product;
                        $po_item = $po_item->node;
                        if ($record['sku'] == $po_item->sku){

                            $variant = Variant::where("sku","=",$po_item->sku)->first();
                            //var_dump($variant->product->product_type);

                       		// Actualiza los registros //


                       		//$record'id' => $po->purchase_order->po_id,
            	            $record['ordered'] = $po_item->quantity; 
                            $record['po'] = $poextended->po_number; 
                            $record['received'] = $po_item->quantity_received;
                            $record['pending'] = $po_item->quantity - $po_item->quantity_received;
                            $record['status'] = $po_item->fulfillment_status;
                            $record['total'] = $poextended->total_price;

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


            foreach ($poextended->line_items as $po_item){

                $po_item = $po_item->node;
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
                        'po' => $poextended->po_number,
                        'sku' => $po_item->sku,
                        'status' => $po_item->fulfillment_status,
                        'ordered' => $po_item->quantity,
                        'received' => $po_item->quantity_received,
                        'pending' => $po_item->quantity - $po_item->quantity_received,
                        'total' => $poextended->total_price,
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
            foreach ($poextended->line_items as $po_item){
                $po_item = $po_item->node;
                $orderTotalItemsReceived += (0 + $po_item->quantity_received);
                $orderTotalItems += (0 + $po_item->quantity);
            }


            //Genera las actualizaciones
            $operation = 'insert';
            $operationalEntry = '';
            foreach ($listFeedOrders->getEntries() as $entry) {
               $record = $entry->getValues();
               if ($record['id'] == $po->purchase_order->po_id || $record['legacyid'] == $poextended->legacy_id){
                    $operationalEntry = $entry;
                    if ($poextended->fulfillment_status == 'canceled' || $poextended->fulfillment_status == 'cancelled'){
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
                    $record['poname'] = htmlspecialchars($poextended->po_number); 
                    $record['status'] = $poextended->fulfillment_status;
                    $record['legacyid'] = $poextended->legacy_id;
                    $record['expecteddate'] = '';
                    $record['vendor'] = htmlspecialchars($poextended->vendor_name);
                    $record['totalcost'] = $poextended->total_price;
                    $record['totalitems'] = $orderTotalItems;
                    $record['itemsreceived'] = $orderTotalItemsReceived;
                    $record['pendingitems'] = $orderTotalItems - $orderTotalItemsReceived;
                    //Registra la actualización
                    $operationalEntry->update($record);
                    break;

                case 'insert':
                    $listFeedOrders->insert([
                        'id' => $po->purchase_order->po_id,
                        'legacyid' => $poextended->legacy_id,
                        'poname' => htmlspecialchars($poextended->po_number),
                        'status' => $poextended->fulfillment_status,
                        'createddate' => $poextended->created_at,
                        'expecteddate' => '',
                        'vendor' => htmlspecialchars($poextended->vendor_name),
                        'totalcost' => $poextended->total_price,
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
            //$poDb = PurchaseOrder::where('po_id','=',$po->purchase_order->po_id)->first();
            $poDb = PurchaseOrder::whereRaw("po_id = '".$po->purchase_order->po_id."' || po_id_legacy = '".$poextended->legacy_id."' || po_id_token = '".$poextended->id."'")->first();
            if ($poDb == null){//No ha sido resgistrada aún
                $poDb = new PurchaseOrder();

                //Registra los identificadores de PO de shiphero, SON TRES!!!!!
                $poDb->po_id = $po->purchase_order->po_id;
                $poDb->po_id_token = $poextended->id;
                $poDb->po_id_legacy = $poextended->legacy_id;

                $poDb->po_number = $poextended->po_number;
                if ($poextended->po_date != 'None'){
                    $poDb->po_date = $poextended->po_date;
                }
                $poDb->fulfillment_status = $poextended->fulfillment_status;
                if (isset($poextended->shipping_price))
                    $poDb->sh_cost = $poextended->shipping_price;
                $poDb->save();

                //3.2. Se registran los PO Items
                for ($i = 0; $i < count($po->purchase_order->line_items);$i++){

                    $itemExt = $poextended->line_items[$i];
                    $itemExt = $itemExt->node;
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

                $poDb->po_number = $poextended->po_number;
                if ($poextended->po_date != 'None'){
                    $poDb->po_date = $poextended->po_date;
                }
                //Actualiza los identificadores de PO de shiphero, SON TRES!!!!!
                $poDb->po_id = $po->purchase_order->po_id;
                $poDb->po_id_token = $poextended->id;
                $poDb->po_id_legacy = $poextended->legacy_id;


                $poDb->fulfillment_status = $poextended->fulfillment_status;
                if (isset($poextended->shipping_price))
                    $poDb->sh_cost = $poextended->shipping_price;
                $poDb->save();

                for ($i = 0; $i < count($po->purchase_order->line_items);$i++){

                    $itemShort = $po->purchase_order->line_items[$i];
                    //$itemExt = $poextended->po->results->items[$i];
                    foreach ($poextended->line_items as $tmpItem){
                        $tmpItem = $tmpItem->node;
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
                        //echo "Cantidad para el elemento ".$itemRaw->sku.": ".$ctrlUpdates[$itemRaw->sku]."\n";
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
            

            $shopifyApi = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));
            $publishValidatorByImage = new ProductPublishValidatorByImage();
            $tagger = new ProductTaggerForNewResTag();
            $findifyApi = new Findify(env('FINDIFY_ENDPOINT'));
            $remoteShopifyProductGetter = new RemoteProductGetterBySku();
            $publisher = new AutomaticProductPublisher();
            

            $htmlEmailBody = '';

            $actualIdProduct = '';
            $qtyPublishedProducts = 0;
            foreach($poextended->line_items as $shItem){
                $shItem = $shItem->node;
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

                $this->sendPublisingReport("Next are the results of publishing products of PO No.".$poextended->po_number." to sleefs.com store:<br /><br />\n\n".$htmlEmailBody,"Publishing items report for PO: ".$poextended->po_number);

            }
        } 
        /*
            
            6.  Despacha la adición/modificación de la PO hacía la plataforma
                de monday.com
        */

        if ($debug[5] == true){

            $pulseNameExtractor = new MondayPulseNameExtractor();
            $gqlClientForMonday = new GraphQLClient(env('MONDAY_GRAPHQL_BASEURL'),array("Authorization: ".env('MONDAY_APIKEY')));
            $mondayApi = new MondayGqlApi($gqlClientForMonday);

            //6.1 Define el nombre del pulse
            $pulseName = $pulseNameExtractor->extractPulseName($poextended->po_number,$poextended->vendor_name,$this->mondayValidVendors);

            if ($pulseName != false){

                //6.2 Determina si el pulse ya está registrado en la DB local, si no lo está, lo crea.
                $pulses = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
                $pulse = '';
                if($pulses->count() > 0)
                {
                    $pulse = $pulses->get(0);
                }
                else
                {
                    //Si no tiene el objeto tipo Pulse creado en la DB, genera uno
                    $pulse = new Pulse();
                    if (isset($poDb) && isset($poDb->id)){ // $poDb Objeto tipo: PurchaseOrder (Modelo laravel)
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
                //6.4   Determina el grupo al que debe pertenecer el pulso


                //=======================================================
                //6.3   Verifica si ya existe un pulse en el tablero con 
                //      el mismo nombre y/o ID del pulse en monday.com
                $fullPulse = $mondayApi->getFullPulse($pulse,env('MONDAY_BOARD'));
                if ($fullPulse == null)
                {
                    //6.4 NO EXISTE el pulse en monday.com, entonces, lo genera.

                    //6.4.1   Recupera el grupo al que pertenece la PO
                    $groupChecker = new MondayGroupChecker();
                    $group = $groupChecker->getGroup($poextended->created_at,env('MONDAY_BOARD'),$mondayApi);
                    if ($group==null){
                        //6.5   Genera un nuevo grupo
                        $groupTitle = $groupChecker->getCorrectGroupName ($poextended->created_at);
                        $data = array(
                            'group_name' => $groupTitle
                        );
                        $group = $mondayApi->addGroupToBoard(env('MONDAY_BOARD'),$data);
                        $group = $group->data->create_group;
                    }

                    $pulseData = array(
                        'item_name' => $pulse->name,
                        'group_id' => $group->id
                    );
                    $newPulse = $mondayApi->createPulse(env('MONDAY_BOARD'),$pulseData);
                    $fullPulse = $mondayApi->getFullPulse($pulse,env('MONDAY_BOARD'));
                }
                if ($pulse->idmonday=='' || $pulse->mon_board=='' || $pulse->mon_group==''){
                    $pulse->idmonday = $fullPulse->id;
                    $pulse->mon_board = env('MONDAY_BOARD');
                    $pulse->mon_group = $fullPulse->group->id;
                    $pulse->save();
                }
                //======================================================
                //6.5   Genera las actualizaciones de los campos
                //======================================================
                $pulseGetterValue = new MondayFullPulseColumnGetter();//Recuperador de los valores de las columnas en el $fullPulse
                //6.5.1 Verifica el title del pulse
                $pulseTitleCandidate = $poextended->po_number;
                $pulseTitleCandidate = preg_replace("/".$pulse->name."/","",$pulseTitleCandidate);
                $pulseTitleCandidate = trim($pulseTitleCandidate);                
                $pulseTitle = $pulseGetterValue->getValue($this->mondayPulseColumnMap['title'],$fullPulse);

                if ($pulseTitle != $pulseTitleCandidate || $pulseTitle == ''){
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['title'],'text',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['title'],$pulseTitleCandidate);
                }
                //6.5.2 Verifica el vendor del pulse
                $pulseVendorCandidate = $poextended->vendor_name;              
                $pulseVendor = $pulseGetterValue->getValue($this->mondayPulseColumnMap['vendor'],$fullPulse);
                if ($pulseVendor != $pulseVendorCandidate || $pulseVendor == ''){
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['vendor'],'text',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['vendor'],$pulseVendorCandidate);
                }
                //6.5.3 Verifica el created date del pulse
                $pulseCreatedAtCandidate = substr($poextended->created_at,0,10);
                $pulseCreatedAt = $pulseGetterValue->getValue($this->mondayPulseColumnMap['created date'],$fullPulse);
                if ($pulseCreatedAt != $pulseCreatedAtCandidate || $pulseCreatedAt == ''){
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['created date'],'date',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['created date'],$pulseCreatedAtCandidate);
                }
                //6.5.4 Verifica el expected date del pulse
                $pulseExpectedAtCandidate = substr($poextended->po_date,0,10);
                $pulseExpectedAt = $pulseGetterValue->getValue($this->mondayPulseColumnMap['expected date'],$fullPulse);
                if ($pulseExpectedAt != $pulseExpectedAtCandidate || $pulseExpectedAt == ''){
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['expected date'],'date',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['expected date'],$pulseExpectedAtCandidate);
                }
                //6.5.5 Verifica el received del pulse
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
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['received'],'status',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['received'],$pulseStatusIndexCandidate);
                }

                //6.5.6. Verifica el total de la orden
                $pulseTotalCostCandidate = $poextended->total_price;              
                $pulseTotalCost = $pulseGetterValue->getValue($this->mondayPulseColumnMap['total cost'],$fullPulse);
                if ($pulseTotalCost != $pulseTotalCostCandidate || $pulseTotalCost == '' || $pulseTotalCost == 0.0){
                    //$mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->pulse->id,$this->mondayPulseColumnMap['total cost'],'numeric',$dataPulse);
                    $mondayApi->updatePulse(env('MONDAY_BOARD'),$fullPulse->id,$this->mondayPulseColumnMap['total cost'],$pulseTotalCostCandidate);
                }  
            }
            else{

                //No se pudo recuperar el nombre del pulse a partir del $poExtended->po_number
                $clogger->writeToLog ("No se puede despachar la PO hacia monday.com, título de la PO es irreconocible, PO Number: ".$poextended->po_number.", PO ID: ".$po->purchase_order->po_id,"ERROR");
            }
        }

        /*

            7.  Genera la respuesta al servidor de shiphero
                y bloquea la hoja de cáculo

        */

        
        //$resUnLock = $wsCtrlUnLocker->unLockFile($spreadsheet,$index);//Realiza el desbloqueo del documento
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