<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\Shiphero\SkuRawCollection;
use Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shiphero\InventoryReport;
use Sleefs\Models\Shiphero\InventoryReportItem;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use \Sleefs\Helpers\MondayApi\MondayApi;

use Sleefs\Models\Monday\Pulse;
use Sleefs\Helpers\Monday\MondayVendorValidator;
use Sleefs\Helpers\Monday\MondayPulseNameExtractor;
use Sleefs\Helpers\Monday\MondayGroupChecker;
use Sleefs\Helpers\Monday\MondayFullPulseColumnGetter;

class MondayIntegrationTest extends TestCase {
    private $pos = array();
	private $pulses = array();
    private $extendedPos = array();
	private $mondayUserId = '5277993';
    private $mondayBoard = '322181342';
	public $urlEndPoint, $apiKey, $mondayApi;

	public function setUp(){
        parent::setUp();

        $this->urlEndPoint = env('MONDAY_BASEURL');
        $this->apiKey = env('MONDAY_APIKEY');
        $this->mondayApi = new MondayApi($this->urlEndPoint,$this->apiKey);

        $this->prepareForTests();
    }

    public function testCheckPulseIfExists(){

    	$pulse = Pulse::whereRaw(" idpo='".$this->pos[0]->id."' ")->get();
    	//echo "MMMMMM: 43\n";
    	//print_r($pulse->count());
    	$this->assertEquals(0,$pulse->count(),"Si existen pulsos registrados en la DB");

    }

    public function testCheckIfBoardGroupExists(){
		$groups = $this->mondayApi->getAllBoardGroups($this->mondayBoard);
		$ctrlBoardTitle = false;
		foreach($groups as $group){
			if ($group->title == 'PO September'){
				$ctrlBoardTitle = true;
			}
		}
		$this->assertTrue($ctrlBoardTitle);
    }

    public function testCreateAPulse(){

    	$groups = $this->mondayApi->getAllBoardGroups($this->mondayBoard);
		$ctrlBoardTitle = false;
		$actualGroup = '';
		foreach($groups as $group){
			if ($group->title == 'PO September'){
				$actualGroup = $group;
			}
		}

		$pulseData = array(
			'pulse[name]' => '1909-20',
			'board_id' => $this->mondayBoard,
			'user_id' => $this->mondayUserId,
			'group_id' => $actualGroup->id,
		);
		$newPulse = $this->mondayApi->createPulse($this->mondayBoard,$pulseData);
		//print_r($newPulse->pulse);
		if (preg_match('/^([0-9]{6,10})/',''.$newPulse->pulse->id)){
			$pulse = new Pulse();
			$pulse->idpo = $this->pos[0]->id;
			$pulse->idmonday = $newPulse->pulse->id;
			$pulse->name = $newPulse->pulse->name;
			$pulse->mon_board = $newPulse->pulse->board_id;
			$pulse->mon_group = $newPulse->board_meta->group_id;
			$saveOperationRes = $pulse->save();
		}
		$pulseCopy = Pulse::find($pulse->id);
		//Assertions
		$this->assertRegExp('/^([0-9]{6,10})/',''.$newPulse->pulse->id);
		$this->assertEquals(true,$saveOperationRes);
		$this->assertEquals($newPulse->pulse->id,$pulseCopy->idmonday);
		//Remove the new pulse for after re-test
		$this->mondayApi->deletePulse($newPulse->pulse->id);
    }

    public function testGetAnAlreadyCreatedPulse(){
    	$pulse = Pulse::find(1);
    	$rawPulse = $this->mondayApi->getPulse($pulse->idmonday);
    	$this->assertEquals('1909-05',$rawPulse->name);
    }

    public function testUpdateAnExistingPulse(){
    	$pulse = Pulse::find(1);
    	$dateCreated = strtotime("2019-09-25");
    	$dateExpected = strtotime("2019-09-29");
    	$dataCreated = array('date_str' => date("Y-m-d",$dateCreated));
    	$dataExpected = array('date_str' => date("Y-m-d",$dateExpected));

    	$rawPulse = $this->mondayApi->updatePulse($this->mondayBoard,$pulse->idmonday,'created_date8','date',$dataCreated);
    	$rawPulse = $this->mondayApi->updatePulse($this->mondayBoard,$pulse->idmonday,'expected_date3','date',$dataExpected);

    	$fullPulse = $this->mondayApi->getFullPulse($pulse,$this->mondayBoard);
    	$this->assertEquals('2019-09-25',$fullPulse->column_values[3]->value);
    	$this->assertEquals('2019-09-29',$fullPulse->column_values[4]->value);
    }


    public function testGetExistingPulse(){
    	$pulse = new Pulse();
    	$pulse->idpo = $this->pos[2]->id;
    	$pulse->idmonday = '';
    	$pulse->name = '1909-04';
    	$pulse->mon_board = '';
    	$pulse->mon_group = '';

    	$fullPulse = $this->mondayApi->getFullPulse($pulse,$this->mondayBoard);
    	$this->assertEquals('322181445',$fullPulse->pulse->id);
    }

    /*
        If a PO vendor isn't Good People Sports or DX, it is not a valid candidate PO for monday.com registry
    */
    public function testValidatePOAsMondayCandidate(){

        $arrValidVendors = array('DX Sporting Goods','Good People Sports');
        $validator = new MondayVendorValidator($arrValidVendors);
        $validvendorPO1 = $validator->validateVendor($this->extendedPos[0]->po->results->vendor_name);
        $validvendorPO2 = $validator->validateVendor($this->extendedPos[4]->po->results->vendor_name);
        //Valid vendors assertions
        $this->assertTrue($validvendorPO1);
        $this->assertTrue($validvendorPO2);

        //Invalid vendors assertions
        $validvendorPO3 = $validator->validateVendor($this->extendedPos[1]->po->results->vendor_name);
        $this->assertFalse($validvendorPO3);
    }


    public function testGetPulseNameFromPONumber(){

        $nameExtractor = new MondayPulseNameExtractor();
        $this->assertRegExp('/^[0-9]{4,4}\-{1}[0-9]{1,2}/',$nameExtractor->extractPulseName($this->extendedPos[0]->po->results->po_number));
    }


    public function testGetPulseSuccessFromPONumber(){

        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[1]->po->results->po_number);
        $pulsesOk = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
        $pulse = $pulsesOk->get(0);
        $this->assertEquals(1,$pulsesOk->count());
        $this->assertEquals('322181434',$pulsesOk->get(0)->idmonday);
        $this->assertEquals('322181434',$pulse->idmonday);

    }


    public function testTryTOGetPulseFromPONumberError(){

        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[0]->po->results->po_number);
        $pulsesOk = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
        $this->assertEquals(0,$pulsesOk->count());

    }


    public function testGetCorrectGroupNameFromPulse(){

        $mondayGroupChecker = new MondayGroupChecker();
        $groupName = $mondayGroupChecker->getCorrectGroupName($this->extendedPos[5]->po->results->po_number);
        $this->assertEquals('PO June '.date("Y"),$groupName);

    }

    public function testCheckCorrectGroup(){

        $mondayGroupChecker = new MondayGroupChecker();
        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[5]->po->results->po_number);
        $group = $mondayGroupChecker->getGroup($pulseName,$this->mondayBoard,$this->mondayApi);
        $this->assertRegExp("/^(Po\ June\ 2019)/i",$group->title);
    }


    public function testCheckNotFoundGroup(){

        $mondayGroupChecker = new MondayGroupChecker();
        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[6]->po->results->po_number);
        $group = $mondayGroupChecker->getGroup($pulseName,$this->mondayBoard,$this->mondayApi);
        $this->assertEquals(null,$group);
    }


    public function testDiscoverColumnValueOfFullPulse(){


        $fullPulse = $this->mondayApi->getFullPulse($this->pulses[0],$this->mondayBoard);
        $getter = new MondayFullPulseColumnGetter();
        

        $pulseName = $getter->getValue('name',$fullPulse);
        $pulseTitle = $getter->getValue('title6',$fullPulse);
        $pulseVendor = $getter->getValue('vendor2',$fullPulse);
        $pulseCreatedDate = $getter->getValue('created_date8',$fullPulse);
        $pulseExpectedDate = $getter->getValue('expected_date3',$fullPulse);
        $pulseReceived = $getter->getValue('received',$fullPulse);
        $pulseTotalCost = $getter->getValue('total_cost0',$fullPulse);


        $this->assertEquals('1909-05',$pulseName);
        $this->assertEquals('sw1759',$pulseTitle);
        $this->assertEquals('',$pulseVendor);
        $this->assertEquals(null,$pulseVendor);
        $this->assertEquals('2019-09-25',$pulseCreatedDate);
        $this->assertEquals('2019-09-29',$pulseExpectedDate);
        $this->assertEquals(5,$pulseReceived);
        $this->assertEquals(67.50,$pulseTotalCost);

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
    private function prepareForTests()
    {
     	\Artisan::call('migrate');
		// Adding POs 
		//PO #1
		//Vendor: DX Sporting Goods
		array_push($this->pos, new PurchaseOrder());
        $this->pos[0]->po_id = 1720;
        $this->pos[0]->po_number = '1909-20  SW1773';
        $this->pos[0]->po_date = '2019-09-27 00:00:00';
        $this->pos[0]->fulfillment_status = 'pending';
		$this->pos[0]->save();
        array_push($this->extendedPos,json_decode('{"Message": "success", "code": "200", "po": {"results": {"shipping_name": null, "shipping_method": null, "payment_method": "credit", "tax": 0.0, "vendor_id": 0, "po_id": 1720, "shipping_carrier": null, "items": [{"sku": "CUSTOM-SP", "created_at": "2019-09-16 13:01:15", "sell_ahead": 0, "price": "2.50", "fulfillment_status": "pending", "vendor_sku": "CUSTOM-SP", "product_name": "Custom Spats", "quantity_received": 0, "quantity": 62}, {"sku": "CUSTOM-HB", "created_at": "2019-09-16 13:01:15", "sell_ahead": 0, "price": "1.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Custom Headbands (Regular)", "quantity_received": 0, "quantity": 62}], "discount": "0.00", "warehouse_country": null, "vendor_address2": "", "vendor_address1": "", "packing_note": null, "warehouse_zip": null, "warehouse_name": null, "subtotal": "248", "warehouse_phone": null, "shipping_price": "0.00", "vendor_email": "xxx", "payment_due_by": "net30", "po_date": "2019-09-27 00:00:00", "total_price": "248", "warehouse_state": null, "vendor_city": "", "po_number": "1909-20 SW1773", "description": null, "warehouse_city": null, "updated_shop_with_data": 1, "warehouse_email": null, "vendor_phone": "", "warehouse": "Primary", "vendor_state": "", "tracking_number": "", "vendor_account_number": "", "warehouse_address2": null, "warehouse_address1": null, "fulfillment_status": "pending", "vendor_zip": "", "po_note": "", "vendor_name": "DX Sporting Goods", "created_at": "2019-09-16 13:01:15", "vendor_country": ""}}}'));
		//PO #2
		array_push($this->pos, new PurchaseOrder());
        $this->pos[1]->po_id = 1701;
        $this->pos[1]->po_number = '1909-05  SW1759';
        $this->pos[1]->po_date = '2019-09-05 16:42:00';
        $this->pos[1]->fulfillment_status = 'pending';
		$this->pos[1]->save();
        array_push($this->extendedPos,json_decode('{"Message": "success", "code": "200", "po": {"results": {"shipping_name": null, "shipping_method": null, "payment_method": "credit", "tax": 0.0, "vendor_id": 0, "po_id": 1701, "shipping_carrier": null, "items": [{"sku": "CUSTOM-SP", "created_at": "2019-09-05 20:42:36", "sell_ahead": 0, "price": "2.50", "fulfillment_status": "pending", "vendor_sku": "CUSTOM-SP", "product_name": "Custom Spats", "quantity_received": 0, "quantity": 27}], "discount": "0.00", "warehouse_country": null, "vendor_address2": "", "vendor_address1": "", "packing_note": null, "warehouse_zip": null, "warehouse_name": null, "subtotal": "67.5", "warehouse_phone": null, "shipping_price": "0.00", "vendor_email": "xxx", "payment_due_by": "net30", "po_date": "2019-09-19 00:00:00", "total_price": "67.5", "warehouse_state": null, "vendor_city": "", "po_number": "1909-05  SW1759", "description": null, "warehouse_city": null, "updated_shop_with_data": 1, "warehouse_email": null, "vendor_phone": "", "warehouse": "Primary", "vendor_state": "", "tracking_number": "", "vendor_account_number": "", "warehouse_address2": null, "warehouse_address1": null, "fulfillment_status": "pending", "vendor_zip": "", "po_note": "", "vendor_name": "MMA No Valid Vendor", "created_at": "2019-09-05 20:42:36", "vendor_country": ""}}}'));
		//PO #3
		array_push($this->pos, new PurchaseOrder());
        $this->pos[2]->po_id = 1702;
        $this->pos[2]->po_number = '1909-04 SW1758';
        $this->pos[2]->po_date = '2019-09-05 16:42:00';
        $this->pos[2]->fulfillment_status = 'pending';
		$this->pos[2]->save();
        array_push($this->extendedPos,new \stdClass());
		//
		
        //PO #4
        array_push($this->pos, new PurchaseOrder());
        $this->pos[3]->po_id = 1674;
        $this->pos[3]->po_number = '1908-35 SW1744';
        $this->pos[3]->po_date = '2019-09-02 00:00:00';
        $this->pos[3]->fulfillment_status = 'pending';
        $this->pos[3]->save();
        array_push($this->extendedPos,json_decode('{"Message":"success","code":"200","po":{"results":{"shipping_name":null,"shipping_method":null,"payment_method":"credit","tax":0,"vendor_id":0,"po_id":1674,"shipping_carrier":null,"items":[{"sku":"CUSTOM-SP","created_at":"2019-08-19 16:47:39","sell_ahead":0,"price":"2.50","fulfillment_status":"closed","vendor_sku":"CUSTOM-SP","product_name":"Custom Spats","quantity_received":15,"quantity":15},{"sku":"CUSTOM-SL-1","created_at":"2019-08-19 16:47:39","sell_ahead":0,"price":"1.00","fulfillment_status":"closed","vendor_sku":"","product_name":"Custom Arm Sleeves (Single)","quantity_received":18,"quantity":18}],"discount":"0.00","warehouse_country":null,"vendor_address2":"","vendor_address1":"","packing_note":null,"warehouse_zip":null,"warehouse_name":null,"subtotal":"55.5","warehouse_phone":null,"shipping_price":"0.00","vendor_email":"xxx","payment_due_by":"net30","po_date":"2019-09-02 00:00:00","total_price":"55.5","warehouse_state":null,"vendor_city":"","po_number":"1908-35 SW1744","description":null,"warehouse_city":null,"updated_shop_with_data":0,"warehouse_email":null,"vendor_phone":"","warehouse":"Primary","vendor_state":"","tracking_number":"","vendor_account_number":"","warehouse_address2":null,"warehouse_address1":null,"fulfillment_status":"closed","vendor_zip":"","po_note":"","vendor_name":"DX Sporting Goods","created_at":"2019-08-19 16:47:39","vendor_country":""}}}'));

        //PO #5
        array_push($this->pos, new PurchaseOrder());
        $this->pos[4]->po_id = 1733;
        $this->pos[4]->po_number = '1909-31 New Sep Designs';
        $this->pos[4]->po_date = '2019-09-18 15:54:32';
        $this->pos[4]->fulfillment_status = 'pending';
        $this->pos[4]->save();
        array_push($this->extendedPos,json_decode('{"Message": "success", "code": "200", "po": {"results": {"shipping_name": null, "shipping_method": null, "payment_method": "credit", "tax": 0.0, "vendor_id": 0, "po_id": 1733, "shipping_carrier": null, "items": [{"sku": "SL-CAUTAP-SP-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Spats / Cleat Covers Y / Black/Yellow", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-TH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Tie Headband ONE SIZE / Yellow/Black", "quantity_received": 0, "quantity": 50}, {"sku": "SL-WRNTSU-SP-SM", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Spats / Cleat Covers S/M / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-AS-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Arm Sleeve Y / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-DNGR-AS-S-M", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Arm Sleeve S/M / Yellow/Black", "quantity_received": 0, "quantity": 15}, {"sku": "SL-WRNHUR-AS-Y", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Arm Sleeve Y / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNTSU-AS-S-M", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Arm Sleeve S/M / Black/Yellow/Red", "quantity_received": 0, "quantity": 15}, {"sku": "SL-BIOGRU-SP-SM", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Spats / Cleat Covers S/M / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNTSU-SP-LXL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Spats / Cleat Covers L/XL / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-WH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Headband ONE SIZE / Yellow/Black", "quantity_received": 0, "quantity": 50}, {"sku": "SL-DNGR-SP-SM", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Spats / Cleat Covers S/M / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-AS-XL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Arm Sleeve XL / Black/Yellow", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-AS-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Arm Sleeve Y / Black/Yellow", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNHUR-WH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Headband ONE SIZE / Black/Yellow/Red", "quantity_received": 0, "quantity": 50}, {"sku": "SL-WRNTSU-AS-L", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Arm Sleeve L / Black/Yellow/Red", "quantity_received": 0, "quantity": 15}, {"sku": "SL-DNGR-TH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Tie Headband ONE SIZE / Yellow/Black", "quantity_received": 0, "quantity": 50}, {"sku": "SL-BIOGRU-AS-L", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Arm Sleeve L / Yellow/Black", "quantity_received": 0, "quantity": 15}, {"sku": "SL-WRNTSU-AS-Y", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Arm Sleeve Y / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNTSU-SP-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Spats / Cleat Covers Y / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNHUR-SP-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Spats / Cleat Covers Y / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-AS-S-M", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Arm Sleeve S/M / Black/Yellow", "quantity_received": 0, "quantity": 15}, {"sku": "SL-DNGR-WH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Headband ONE SIZE / Yellow/Black", "quantity_received": 0, "quantity": 50}, {"sku": "SL-BIOGRU-AS-XL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Arm Sleeve XL / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNTSU-AS-XL", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Arm Sleeve XL / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-SP-LXL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Spats / Cleat Covers L/XL / Black/Yellow", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-AS-L", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Arm Sleeve L / Black/Yellow", "quantity_received": 0, "quantity": 15}, {"sku": "SL-WRNHUR-SP-SM", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Spats / Cleat Covers S/M / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNHUR-AS-XL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Arm Sleeve XL / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-DNGR-AS-L", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Arm Sleeve L / Yellow/Black", "quantity_received": 0, "quantity": 15}, {"sku": "SL-WRNHUR-AS-L", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Arm Sleeve L / Black/Yellow/Red", "quantity_received": 0, "quantity": 15}, {"sku": "SL-DNGR-AS-Y", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Arm Sleeve Y / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-AS-S-M", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Arm Sleeve S/M / Yellow/Black", "quantity_received": 0, "quantity": 15}, {"sku": "SL-CAUTAP-SP-SM", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Spats / Cleat Covers S/M / Black/Yellow", "quantity_received": 0, "quantity": 10}, {"sku": "SL-CAUTAP-WH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Caution Tape Headband ONE SIZE / Black/Yellow", "quantity_received": 0, "quantity": 50}, {"sku": "SL-DNGR-SP-LXL", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Spats / Cleat Covers L/XL / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-SP-Y", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Spats / Cleat Covers Y / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-DNGR-AS-XL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Arm Sleeve XL / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNHUR-AS-S-M", "created_at": "2019-09-18 15:54:31", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Arm Sleeve S/M / Black/Yellow/Red", "quantity_received": 0, "quantity": 15}, {"sku": "SL-DNGR-SP-Y", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Danger Spats / Cleat Covers Y / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNHUR-SP-LXL", "created_at": "2019-09-18 15:54:32", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Hurricane Warning Spats / Cleat Covers L/XL / Black/Yellow/Red", "quantity_received": 0, "quantity": 10}, {"sku": "SL-BIOGRU-SP-LXL", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "1.10", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Biohazard Grunge Spats / Cleat Covers L/XL / Yellow/Black", "quantity_received": 0, "quantity": 10}, {"sku": "SL-WRNTSU-WH", "created_at": "2019-09-18 15:54:33", "sell_ahead": 0, "price": "0.50", "fulfillment_status": "pending", "vendor_sku": "", "product_name": "Tsunami Warning Headband ONE SIZE / Black/Yellow/Red", "quantity_received": 0, "quantity": 50}], "discount": "0", "warehouse_country": null, "vendor_address2": "", "vendor_address1": "", "packing_note": null, "warehouse_zip": null, "warehouse_name": null, "subtotal": "465", "warehouse_phone": null, "shipping_price": "0", "vendor_email": "953440032@qq.com", "payment_due_by": "unlimited", "po_date": "2019-10-09 00:00:00", "total_price": "465", "warehouse_state": null, "vendor_city": "", "po_number": "1909-31 New Sep Designs", "description": null, "warehouse_city": null, "updated_shop_with_data": 1, "warehouse_email": null, "vendor_phone": "", "warehouse": "Primary", "vendor_state": "", "tracking_number": "", "vendor_account_number": "", "warehouse_address2": null, "warehouse_address1": null, "fulfillment_status": "pending", "vendor_zip": "", "po_note": "", "vendor_name": "Good People Sports", "created_at": "2019-09-18 15:54:31", "vendor_country": ""}}}'));
        //PO #6
        array_push($this->pos, new PurchaseOrder());
        $this->pos[5]->po_id = 1351;
        $this->pos[5]->po_number = '1906-02 SW1744';
        $this->pos[5]->po_date = '2019-06-01 01:00:00';
        $this->pos[5]->fulfillment_status = 'pending';
        $this->pos[5]->save();
        array_push($this->extendedPos,json_decode('{"Message":"success","code":"200","po":{"results":{"shipping_name":null,"shipping_method":null,"payment_method":"credit","tax":0,"vendor_id":0,"po_id":1351,"shipping_carrier":null,"items":[{"sku":"CUSTOM-SP","created_at":"2019-08-19 16:47:39","sell_ahead":0,"price":"2.50","fulfillment_status":"closed","vendor_sku":"CUSTOM-SP","product_name":"Custom Spats","quantity_received":15,"quantity":15},{"sku":"CUSTOM-SL-1","created_at":"2019-08-19 16:47:39","sell_ahead":0,"price":"1.00","fulfillment_status":"closed","vendor_sku":"","product_name":"Custom Arm Sleeves (Single)","quantity_received":18,"quantity":18}],"discount":"0.00","warehouse_country":null,"vendor_address2":"","vendor_address1":"","packing_note":null,"warehouse_zip":null,"warehouse_name":null,"subtotal":"55.5","warehouse_phone":null,"shipping_price":"0.00","vendor_email":"xxx","payment_due_by":"net30","po_date":"2019-09-02 00:00:00","total_price":"55.5","warehouse_state":null,"vendor_city":"","po_number":"1906-02 SW1744","description":null,"warehouse_city":null,"updated_shop_with_data":0,"warehouse_email":null,"vendor_phone":"","warehouse":"Primary","vendor_state":"","tracking_number":"","vendor_account_number":"","warehouse_address2":null,"warehouse_address1":null,"fulfillment_status":"closed","vendor_zip":"","po_note":"","vendor_name":"DX Sporting Goods","created_at":"2019-08-19 16:47:39","vendor_country":""}}}'));


        array_push($this->pos, new PurchaseOrder());
        $this->pos[6]->po_id = 1178;
        $this->pos[6]->po_number = '1901-12 SW1255';
        $this->pos[6]->po_date = '2019-01-06 01:00:00';
        $this->pos[6]->fulfillment_status = 'pending';
        $this->pos[6]->save();
        array_push($this->extendedPos,json_decode('{"Message":"success","code":"200","po":{"results":{"shipping_name":null,"shipping_method":null,"payment_method":"credit","tax":0,"vendor_id":0,"po_id":1178,"shipping_carrier":null,"items":[{"sku":"CUSTOM-SP","created_at":"2019-01-06 16:47:39","sell_ahead":0,"price":"2.50","fulfillment_status":"closed","vendor_sku":"CUSTOM-SP","product_name":"Custom Spats","quantity_received":15,"quantity":15},{"sku":"CUSTOM-SL-1","created_at":"2019-08-19 16:47:39","sell_ahead":0,"price":"1.00","fulfillment_status":"closed","vendor_sku":"","product_name":"Custom Arm Sleeves (Single)","quantity_received":18,"quantity":18}],"discount":"0.00","warehouse_country":null,"vendor_address2":"","vendor_address1":"","packing_note":null,"warehouse_zip":null,"warehouse_name":null,"subtotal":"55.5","warehouse_phone":null,"shipping_price":"0.00","vendor_email":"xxx","payment_due_by":"net30","po_date":"2019-01-06 01:00:00","total_price":"55.5","warehouse_state":null,"vendor_city":"","po_number":"1901-12 SW1255","description":null,"warehouse_city":null,"updated_shop_with_data":0,"warehouse_email":null,"vendor_phone":"","warehouse":"Primary","vendor_state":"","tracking_number":"","vendor_account_number":"","warehouse_address2":null,"warehouse_address1":null,"fulfillment_status":"closed","vendor_zip":"","po_note":"","vendor_name":"DX Sporting Goods","created_at":"2019-08-19 16:47:39","vendor_country":""}}}'));

        //Pulses
        //Pulse #1
        array_push($this->pulses, new Pulse());
        $this->pulses[0]->idpo = $this->pos[1]->id;
        $this->pulses[0]->idmonday = 322181434;
        $this->pulses[0]->name = '1909-05';
        $this->pulses[0]->mon_board = $this->mondayBoard;
        $this->pulses[0]->mon_group = 'po';
        $this->pulses[0]->save();

    }

}