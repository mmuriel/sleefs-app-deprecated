<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

//use \mdeschermeier\shiphero\Shiphero;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Helpers\Shiphero\SkuRawCollection;
use Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shiphero\InventoryReport;
use Sleefs\Models\Shiphero\InventoryReportItem;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\MondayApi\MondayGqlApi;

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
    private $mondayBoard = '670700889';
	public $mondayApi;

	public function setUp(){
        parent::setUp();
        $gqlClt = new GraphQLClient(env('MONDAY_GRAPHQL_BASEURL'),array("Authorization: ".env('MONDAY_APIKEY')));
        $this->mondayApi = new MondayGqlApi($gqlClt);
        $this->prepareForTests();
    }

    public function testCheckPulseIfExists(){

    	$pulse = Pulse::whereRaw(" idpo='".$this->pos[1]->id."' ")->get();
    	//echo "MMMMMM: 43\n";
    	//print_r($pulse);
    	$this->assertEquals(0,$pulse->count(),"Si existen pulsos registrados en la DB");

    }

    public function testCheckIfBoardGroupExists(){
		$groups = $this->mondayApi->getAllBoardGroups($this->mondayBoard);
		$ctrlBoardTitle = false;
		foreach($groups as $group){
			if ($group->title == 'PO October 2020'){
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
			if ($group->title == 'PO October 2020'){
				$actualGroup = $group;
			}
		}

		$pulseData = array(
			'item_name' => '2010-07',
            'group_id' => 'po_october_2020',
            'column_values' => array(
                'title6' => '2-Layer NG',
                'vendor2' => 'Good People Sports',
                'created_date8' => '2020-10-13',
                'expected_date3' => '2020-11-02',
                'total_cost0' => '1200',
                'received' => '2'
            )
		);
		$newPulse = $this->mondayApi->createPulse($this->mondayBoard,$pulseData);
		if (isset($newPulse->data->create_item->id) && preg_match('/^([0-9]{6,10})/',''.$newPulse->data->create_item->id)){
			$pulse = new Pulse();
			$pulse->idpo = $this->pos[4]->id;
			$pulse->idmonday = $newPulse->data->create_item->id;
			$pulse->name = $newPulse->data->create_item->name;
			$pulse->mon_board = $this->mondayBoard;
			$pulse->mon_group = $pulseData['group_id'];
			$saveOperationRes = $pulse->save();
		}
		$pulseCopy = Pulse::find($pulse->id);
		//Assertions
		$this->assertRegExp('/^([0-9]{6,10})/',''.$newPulse->data->create_item->id);
		$this->assertEquals(true,$saveOperationRes);
		$this->assertEquals($newPulse->data->create_item->id,$pulseCopy->idmonday);
		//Remove the new pulse for after re-test
		$this->mondayApi->deletePulse($newPulse->data->create_item->id);
    }

    public function testGetAnAlreadyCreatedPulse(){
    	$pulse = Pulse::find(1);
    	$rawPulse = $this->mondayApi->getPulse($pulse->idmonday);
    	$this->assertEquals('2010-20',$rawPulse->name);
    }

    public function testUpdateAnExistingPulse(){
    	$pulse = Pulse::find(1);
    	$dateCreated = strtotime("2019-09-25");
    	$dateExpected = strtotime("2019-09-29");
    	$dataCreated = array('date_str' => date("Y-m-d",$dateCreated));
    	$dataExpected = array('date_str' => date("Y-m-d",$dateExpected));

    	$rawPulse = $this->mondayApi->updatePulse($this->mondayBoard,$pulse->idmonday,'created_date8',$dataCreated['date_str']);
    	$rawPulse = $this->mondayApi->updatePulse($this->mondayBoard,$pulse->idmonday,'expected_date3',$dataExpected['date_str']);

    	$fullPulse = $this->mondayApi->getPulse($pulse->idmonday);
    	$this->assertEquals('2019-09-25',$fullPulse->column_values[2]->text);
    	$this->assertEquals('2019-09-29',$fullPulse->column_values[3]->text);
    }


    /*
        If a PO vendor isn't Good People Sports or DX, it is not a valid candidate PO for monday.com registry
    */
    public function testValidatePOAsMondayCandidate(){

        $arrValidVendors = array('DX Sporting Goods','Good People Sports');
        $validator = new MondayVendorValidator($arrValidVendors);
        $validvendorPO1 = $validator->validateVendor($this->extendedPos[0]->line_items[0]->node->vendor->name);
        $validvendorPO2 = $validator->validateVendor($this->extendedPos[4]->line_items[0]->node->vendor->name);
        //Valid vendors assertions
        $this->assertTrue($validvendorPO1);
        $this->assertTrue($validvendorPO2);

        //Invalid vendors assertions
        $validvendorPO3 = $validator->validateVendor($this->extendedPos[1]->line_items[0]->node->vendor->name);
        $this->assertFalse($validvendorPO3);
    }


    public function testGetPulseNameFromPONumber(){

        $nameExtractor = new MondayPulseNameExtractor();
        $this->assertRegExp('/^[0-9]{4,4}\-{1}[0-9]{1,2}/',$nameExtractor->extractPulseName($this->extendedPos[0]->po_number));
    }


    public function testGetPulseSuccessFromPONumber(){

        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[0]->po_number);
        $pulsesOk = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
        $pulse = $pulsesOk->get(0);
        $this->assertEquals(1,$pulsesOk->count());
        $this->assertEquals('807861772',$pulsesOk->get(0)->idmonday);
        $this->assertEquals('807861772',$pulse->idmonday);

    }


    public function testTryTOGetPulseFromPONumberError(){

        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[1]->po_number);
        $pulsesOk = Pulse::whereRaw(" (name='{$pulseName}') ")->get();
        $this->assertEquals(0,$pulsesOk->count());

    }


    public function testGetCorrectGroupNameFromPulse(){

        $mondayGroupChecker = new MondayGroupChecker();
        $groupName = $mondayGroupChecker->getCorrectGroupName($this->extendedPos[6]->po_number);
        $this->assertEquals('PO September '.date("Y"),$groupName);

    }

    public function testCheckCorrectGroup(){

        $mondayGroupChecker = new MondayGroupChecker();
        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[0]->po_number);
        $group = $mondayGroupChecker->getGroup($pulseName,$this->mondayBoard,$this->mondayApi);
        $this->assertRegExp("/^(Po\ October\ 2020)/i",$group->title);
    }


    public function testCheckNotFoundGroup(){

        $mondayGroupChecker = new MondayGroupChecker();
        $nameExtractor = new MondayPulseNameExtractor();
        $pulseName = $nameExtractor->extractPulseName($this->extendedPos[6]->po_number);
        $group = $mondayGroupChecker->getGroup($pulseName,$this->mondayBoard,$this->mondayApi);
        $this->assertEquals(null,$group);
    }


    public function testDiscoverColumnValueOfFullPulse(){


        $fullPulse = $this->mondayApi->getPulse($this->pulses[0]->idmonday,$this->mondayBoard);
        $getter = new MondayFullPulseColumnGetter();
        $pulseName = $getter->getValue('name',$fullPulse);
        $pulseTitle = $getter->getValue('title6',$fullPulse);
        $pulseVendor = $getter->getValue('vendor2',$fullPulse);
        $pulseCreatedDate = $getter->getValue('created_date8',$fullPulse);
        $pulseExpectedDate = $getter->getValue('expected_date3',$fullPulse);
        $pulseReceived = $getter->getValue('received',$fullPulse);
        $pulseTotalCost = $getter->getValue('total_cost0',$fullPulse);


        $this->assertEquals('2010-20',$pulseName);
        $this->assertEquals('D1021',$pulseTitle);
        $this->assertEquals('DX Sporting Goods',$pulseVendor);
        $this->assertEquals('2019-09-25',$pulseCreatedDate);
        $this->assertEquals('2019-09-29',$pulseExpectedDate);
        $this->assertEquals(2,$pulseReceived);
        $this->assertEquals(3349.5,$pulseTotalCost);

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
        $this->pos[0]->po_id = 2267;
        $this->pos[0]->po_number = '2010-20  D1021';
        $this->pos[0]->po_date = '2020-11-02 00:00:00';
        $this->pos[0]->fulfillment_status = 'pending';
		$this->pos[0]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1OTUyNTk=","legacy_id":595259,"po_number":"2010-20  D1021","po_date":"2020-11-02 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjE4Mzg1","created_at":"2020-10-19 18:20:43","fulfillment_status":"pending","po_note":null,"description":null,"subtotal":"630","shipping_price":"0.00","total_price":"630","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjgwMDc2Mjg=","price":"1.25","po_id":"UHVyY2hhc2VPcmRlcjo1OTUyNTk=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE4Mzg1","po_number":null,"sku":"CUSTOM-SL-1","barcode":"CUSTOM-SL-1","note":null,"quantity":504,"quantity_received":0,"quantity_rejected":0,"product_name":"Custom Arm Sleeves (Single)","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE4Mzg1","name":"DX Sporting Goods","email":"xxx","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"DX Sporting Goods"}'));
		//PO #2
		array_push($this->pos, new PurchaseOrder());
        $this->pos[1]->po_id = 2266;
        $this->pos[1]->po_number = '57951563501025491 Turf Tape';
        $this->pos[1]->po_date = '2020-11-02 00:00:00';
        $this->pos[1]->fulfillment_status = 'pending';
		$this->pos[1]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1OTQ4NjY=","legacy_id":594866,"po_number":"57951563501025491 Turf Tape","po_date":"2020-11-02 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjI2OTY2MQ==","created_at":"2020-10-19 13:23:42","fulfillment_status":"pending","po_note":null,"description":null,"subtotal":"1440","shipping_price":"0.00","total_price":"1440","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjgwMDQxNzE=","price":"1.80","po_id":"UHVyY2hhc2VPcmRlcjo1OTQ4NjY=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjI2OTY2MQ==","po_number":null,"sku":"SL-WHT-TP","barcode":"SL-WHT-TP","note":null,"quantity":500,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic White Turf Tape","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjI2OTY2MQ==","name":"Wuxi Jieyu Microfiber Fabric Manufacturing","email":"chocogwan1@wxjieyu.cn","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjgwMDQxNzI=","price":"1.80","po_id":"UHVyY2hhc2VPcmRlcjo1OTQ4NjY=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjI2OTY2MQ==","po_number":null,"sku":"SL-PNK-TP","barcode":"SL-PNK-TP","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Hue Pink Turf Tape ","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjI2OTY2MQ==","name":"Wuxi Jieyu Microfiber Fabric Manufacturing","email":"chocogwan1@wxjieyu.cn","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjgwMDQxNzM=","price":"1.80","po_id":"UHVyY2hhc2VPcmRlcjo1OTQ4NjY=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjI2OTY2MQ==","po_number":null,"sku":"SL-RED-TP","barcode":"SL-RED-TP","note":null,"quantity":200,"quantity_received":0,"quantity_rejected":0,"product_name":"Hue Red Turf Tape ","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjI2OTY2MQ==","name":"Wuxi Jieyu Microfiber Fabric Manufacturing","email":"chocogwan1@wxjieyu.cn","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"Wuxi Jieyu Microfiber Fabric Manufacturing"}'));
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
        $this->pos[3]->po_id = 2264;
        $this->pos[3]->po_number = '2010-19 Mask Re';
        $this->pos[3]->po_date = '2020-11-02 00:00:00';
        $this->pos[3]->fulfillment_status = 'pending';
        $this->pos[3]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","legacy_id":593717,"po_number":"2010-19 Mask Re","po_date":"2020-11-02 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjY5NDQy","created_at":"2020-10-16 13:06:07","fulfillment_status":"pending","po_note":null,"description":null,"subtotal":"1495","shipping_price":"1495","total_price":"2990","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTk=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-GRNGRN-MK","barcode":"SL-GRNGRN-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Green Grin Flat Face Mask ONE SIZE \/ Purple\/Green","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTM=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-TIEDYE-MK","barcode":"SL-TIEDYE-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Tie Dye Flat Face Mask ONE SIZE \/ Multicolor","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODI=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-DANICO-KMK","barcode":"SL-DANICO-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Dangerous Icons Kids Essential Face Mask ONE SIZE \/ Multicolor","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODQ=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-UNIDRE-KMK","barcode":"SL-UNIDRE-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Unicorns Dream Kids Essential Face Mask ONE SIZE \/ White\/Blue","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTQ=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-SKULL-MK","barcode":"SL-SKULL-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Skull Blue Flat Face Mask ONE SIZE \/ Blue","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTU=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-BARWIR-MK","barcode":"SL-BARWIR-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Barbed Wires DIY Face Mask ONE SIZE \/ Black\/White","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODE=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-SPIPNK-KMK","barcode":"SL-SPIPNK-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Sprinkles Pink Kids Essential Face Mask ONE SIZE \/ Pink","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODY=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-SLEUNI-KMK","barcode":"SL-SLEUNI-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Sleeping Unicorn Kids Essential Face Mask ONE SIZE \/ White\/Purple","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODg=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-LIGPNK-MK","barcode":"SL-LIGPNK-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Light Pink Flat Face Mask ONE SIZE \/ Pink","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODU=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-DIPABLU-KMK","barcode":"SL-DIPABLU-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Dinosaurs Pattern Blue Kids Essential Face Mask ONE SIZE \/ Multicolor","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODM=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-MERRAI-KMK","barcode":"SL-MERRAI-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Mermaid Rainbow Kids Essential Face Mask ONE SIZE \/ Teal\/Yellow","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTg=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-NEBULA-MK","barcode":"SL-NEBULA-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Nebula Flat Face Mask ONE SIZE \/ Purple","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTY=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-AQU-MK","barcode":"SL-AQU-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Azure Flat Face Mask ONE SIZE \/ Blue","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODk=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-NAVY-MK","barcode":"SL-NAVY-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Navy Flat Face Mask ONE SIZE \/ Blue","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4Nzk=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-WATSMI-KMK","barcode":"SL-WATSMI-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Watermelon Smile Kids Essential Face Mask ONE SIZE \/ Red\/Green","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTA=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-PUR-MK","barcode":"SL-PUR-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Purple Flat Face Mask ONE SIZE \/ Purple","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODc=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-BENJ-MK","barcode":"SL-BENJ-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Money Benjamins Flat Face Mask ONE SIZE \/ Green","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTc=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-GLD-MK","barcode":"SL-GLD-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Gold DIY Face Mask ONE SIZE \/ Gold","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTE=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-VAMSKU-MK","barcode":"SL-VAMSKU-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Vampire Skull DIY Face Mask ONE SIZE \/ Black\/White","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4ODA=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-DINROB-KMK","barcode":"SL-DINROB-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Dino Robot Kids Essential Face Mask ONE SIZE \/ Yellow","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4Nzg=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-BUTFAL-KMK","barcode":"SL-BUTFAL-KMK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Butterflies Fall Kids Essential Face Mask ONE SIZE \/ Blue\/Pink","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY4OTI=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-PNKDON-MK","barcode":"SL-PNKDON-MK","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Pink Donuts Flat Face Mask ONE SIZE \/ Pink","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5ODY5MDA=","price":"0.65","po_id":"UHVyY2hhc2VPcmRlcjo1OTM3MTc=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":"2010-19 Mask Re","sku":"SL-GOOVIB-MK","barcode":"298076452","note":null,"quantity":100,"quantity_received":0,"quantity_rejected":0,"product_name":"Good Vibes DIY Face Mask ONE SIZE \/ Green","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"Good People Sports"}'));

        //PO #5
        array_push($this->pos, new PurchaseOrder());
        $this->pos[4]->po_id = 2251;
        $this->pos[4]->po_number = '2010-07 2-Layer NG';
        $this->pos[4]->po_date = '2020-10-26 00:00:00';
        $this->pos[4]->fulfillment_status = 'pending';
        $this->pos[4]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1OTEyMDI=","legacy_id":591202,"po_number":"2010-07 2-Layer NG","po_date":"2020-10-26 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjY5NDQy","created_at":"2020-10-13 18:52:36","fulfillment_status":"pending","po_note":null,"description":null,"subtotal":"1200","shipping_price":"0.00","total_price":"1200","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc5NTE4NTk=","price":"1.20","po_id":"UHVyY2hhc2VPcmRlcjo1OTEyMDI=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjY5NDQy","po_number":null,"sku":"SL-BLK-RHN","barcode":"SL-BLK-RHN","note":null,"quantity":1000,"quantity_received":0,"quantity_rejected":0,"product_name":"Basic Black 2-Layer Neck Gaiter ONE SIZE \/ Black","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjY5NDQy","name":"Good People Sports","email":"953440032@qq.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"Good People Sports"}'));
        //PO #6
        array_push($this->pos, new PurchaseOrder());
        $this->pos[5]->po_id = 2217;
        $this->pos[5]->po_number = 'HB\/AM20200914-D';
        $this->pos[5]->po_date = '2020-10-19 00:00:00';
        $this->pos[5]->fulfillment_status = 'pending';
        $this->pos[5]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","legacy_id":571060,"po_number":"HB\/AM20200914-D","po_date":"2020-10-19 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjE0NTk3Mg==","created_at":"2020-09-14 13:36:58","fulfillment_status":"pending","po_note":"9\/14 Paid 6,651.00 Deposit\n10\/9 Paid 20,055.00 Balance","description":null,"subtotal":"22170","shipping_price":"4536","total_price":"26706","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2NzgyMjk=","price":"5.06","po_id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE0NTk3Mg==","po_number":null,"sku":"SL-SLVR-VS","barcode":"SL-SLVR-VS","note":null,"quantity":1000,"quantity_received":0,"quantity_rejected":0,"product_name":"Silver Moonstone Helmet Eye-Shield Color Tinted Visor Silver","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE0NTk3Mg==","name":"Hubo Sports Products","email":"hubosports01@hubo-sports.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2NzgyMzA=","price":"4.74","po_id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE0NTk3Mg==","po_number":null,"sku":"SL-BLK-VS","barcode":"SL-BLK-VS","note":null,"quantity":1000,"quantity_received":0,"quantity_rejected":0,"product_name":"Black Diamond Helmet Eye-Shield Color Tinted Visor Black","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE0NTk3Mg==","name":"Hubo Sports Products","email":"hubosports01@hubo-sports.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2NzgyMzE=","price":"4.74","po_id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE0NTk3Mg==","po_number":null,"sku":"SL-REDRGCLR-VS","barcode":"SL-REDRGCLR-VS","note":null,"quantity":1000,"quantity_received":0,"quantity_rejected":0,"product_name":"Red Rage Clear Helmet Eye-Shield Visor Red","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE0NTk3Mg==","name":"Hubo Sports Products","email":"hubosports01@hubo-sports.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2NzgyMzI=","price":"5.23","po_id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE0NTk3Mg==","po_number":null,"sku":"SL-BIFRST-VS","barcode":"SL-BIFRST-VS","note":null,"quantity":1000,"quantity_received":0,"quantity_rejected":0,"product_name":"Bifrost Clear Rainbow Helmet Eye-Shield Visor Multicolor","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE0NTk3Mg==","name":"Hubo Sports Products","email":"hubosports01@hubo-sports.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}},{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2NzgyMzM=","price":"0.60","po_id":"UHVyY2hhc2VPcmRlcjo1NzEwNjA=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE0NTk3Mg==","po_number":null,"sku":"SL-BLK-CLP","barcode":"SL-BLK-CLP","note":null,"quantity":4000,"quantity_received":0,"quantity_rejected":0,"product_name":"Visor Clips","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE0NTk3Mg==","name":"Hubo Sports Products","email":"hubosports01@hubo-sports.com","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"Hubo Sports Products"}'));


        array_push($this->pos, new PurchaseOrder());
        $this->pos[6]->po_id = 2219;
        $this->pos[6]->po_number = '2009-18 SW1888';
        $this->pos[6]->po_date = '2020-09-29 00:00:00';
        $this->pos[6]->fulfillment_status = 'pending';
        $this->pos[6]->save();
        array_push($this->extendedPos,json_decode('{"id":"UHVyY2hhc2VPcmRlcjo1NzIzMjg=","legacy_id":572328,"po_number":"2009-18 SW1888","po_date":"2020-09-29 00:00:00","account_id":"QWNjb3VudDoxMTU3","vendor_id":"VmVuZG9yOjE4Mzg1","created_at":"2020-09-15 14:56:25","fulfillment_status":"pending","po_note":null,"description":null,"subtotal":"712.5","shipping_price":"0.00","total_price":"712.5","line_items":[{"node":{"id":"UHVyY2hhc2VPcmRlckxpbmVJdGVtOjc2OTI3NTg=","price":"1.25","po_id":"UHVyY2hhc2VPcmRlcjo1NzIzMjg=","account_id":"QWNjb3VudDoxMTU3","warehouse_id":"V2FyZWhvdXNlOjE2ODQ=","vendor_id":"VmVuZG9yOjE4Mzg1","po_number":null,"sku":"CUSTOM-SL-1","barcode":"CUSTOM-SL-1","note":null,"quantity":570,"quantity_received":0,"quantity_rejected":0,"product_name":"Custom Arm Sleeves (Single)","fulfillment_status":"pending","vendor":{"id":"VmVuZG9yOjE4Mzg1","name":"DX Sporting Goods","email":"xxx","account_id":"QWNjb3VudDoxMTU3","account_number":null}}}],"vendor_name":"DX Sporting Goods"}'));

        //Pulses
        //Pulse #1
        array_push($this->pulses, new Pulse());
        $this->pulses[0]->idpo = $this->pos[0]->id;
        $this->pulses[0]->idmonday = 807861772;
        $this->pulses[0]->name = '2010-20';
        $this->pulses[0]->mon_board = $this->mondayBoard;
        $this->pulses[0]->mon_group = 'po_october_2020';
        $this->pulses[0]->save();

    }

}