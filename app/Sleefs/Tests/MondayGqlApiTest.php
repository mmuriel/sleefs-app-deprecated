<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;


use \Sleefs\Helpers\MondayApi\MondayGqlApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;
use Sleefs\Models\Monday\Pulse;

class MondayGqlApiTest extends TestCase {

	public $mondayGqlApi, $gqlClient;

	public function setUp()
    {
        parent::setUp();
        $this->gqlClient = new GraphQLClient(env('MONDAY_GRAPHQL_BASEURL'),array('Authorization: '.env('MONDAY_APIKEY').''));
        $this->mondayGqlApi = new MondayGqlApi($this->gqlClient);
        $this->prepareForTests();
    }
 

	public function testGetAllBoards(){

		$allBoards = $this->mondayGqlApi->getAllBoards();
		$this->assertEquals(2,count($allBoards->data->boards));
		$this->assertEquals('CP Pending POs - MMA -DEV',$allBoards->data->boards[1]->name);

	}


	public function testGetABoard(){

		//ID of board to test: https://sleefs.monday.com/boards/670700889
		$idBoard = 670700889;
		$board = $this->mondayGqlApi->getBoard($idBoard);
		//print_r($board);
		$this->assertEquals('CP Pending POs - MMA -DEV',$board->name);
		//$this->assertEquals('POs',$allBoards[0]->name);

	}


	public function testGetBoardPulses(){

		//ID of board to test: https://sleefs.monday.com/boards/670700889
		$idBoard = 670700889;
		$pulses = $this->mondayGqlApi->getBoardPulses($idBoard,'(page:1,limit:25)');
		//print_r($pulses);
		$this->assertEquals(25,count($pulses));
		$this->assertEquals('D1021',$pulses[0]->column_values[0]->text);

	}


	public function testGetPulse(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$pulseId = '670700953';
		$pulse = $this->mondayGqlApi->getPulse($pulseId);
		//print_r($pulse);
		$this->assertEquals('2007-18',$pulse->name);
	}


	public function testGetFullPulseFromPulseNameError(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$idBoard = 670700889;
		$newPulse = new Pulse();
		$newPulse->name = 'P1201813-800';//P120181252
		$newPulse->idmonday = '';
		$pulse = $this->mondayGqlApi->getFullPulse($newPulse,$idBoard);
		$this->assertEquals(null,$pulse);
	}


	public function testGetFullPulseFromPulseName(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$idBoard = 670700889;
		$newPulse = new Pulse();
		$newPulse->name = 'P120181252';//P120181252
		$newPulse->idmonday = '';
		$pulse = $this->mondayGqlApi->getFullPulse($newPulse,$idBoard);
		$this->assertEquals('801976673',$pulse->id);
	}


	public function testGetFullPulseFromMondayId(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$idBoard = 670700889;
		$newPulse = new Pulse();
		$newPulse->name = '';//P120181252
		$newPulse->idmonday = '801976295';
		$pulse = $this->mondayGqlApi->getFullPulse($newPulse,$idBoard);
		$this->assertEquals('P120181251',$pulse->name);
	}


	public function testGetPulseError(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$pulseId = '81434';
		$pulse = $this->mondayGqlApi->getPulse($pulseId);
		$this->assertObjectHasAttribute('error',$pulse);
	}


	public function testAddPulseToBoardThenDeleteIt(){

		//ID of board to test: https://sleefs.monday.com/boards/670700889
		$idBoard = 670700889;
		$data = array(
			'item_name' => 'P120181250-AUTOMATED TEST',
			'column_values' => array(
				'title6' => 'P120181250-AUTOMATED TEST',
				'vendor2' => 'People Sports',
				'created_date8' => '2020-10-16 23:04:21',
				'expected_date3' => '2020-10-26 10:00:00',
				'pay' => 'Pending',
				'received' => ''
			)
		);

		$newPulse = $this->mondayGqlApi->createPulse($idBoard,$data);
		$this->assertEquals('P120181250-AUTOMATED TEST',$newPulse->data->create_item->name);
		//It deletes the temporary new created pulse
		$delResponse = $this->mondayGqlApi->deletePulse($newPulse->data->create_item->id);
		$this->assertTrue(isset($delResponse->data->delete_item->id));

	}



	public function testGetBoardGroups(){
		//ID of board to test: https://sleefs.monday.com/boards/670700889
		$idBoard = 670700889;
		$groups = $this->mondayGqlApi->getAllBoardGroups($idBoard);
		$this->assertEquals(18,count($groups));
	}


	public function testAddAndDeleteBoardGroup(){

		$idBoard = 670700889;
		$date = time();
		$groupTitle ="PO ".ucfirst(date("F",$date));
		$data = array(
			'group_name' => $groupTitle
		);
		$newGroup = $this->mondayGqlApi->addGroupToBoard($idBoard,$data);
		//Asserting the add action
		$this->assertRegExp("/^po_october/",$newGroup->data->create_group->id);


		$delResponse = $this->mondayGqlApi->delBoardGroup($idBoard,$newGroup->data->create_group->id);
		//Asserting the delete action
		$this->assertRegExp("/^po_october([0-9]{2,6})/",$delResponse->data->delete_group->id);


	}

	
	public function testAddPulseToBoardAndModifyFields(){

		//ID of board to test: https://sleefs.monday.com/boards/230782591
		$idBoard = 670700889;
		$basicDataPulse = array(
			'item_name' => 'P120187002-MMA-TEST',
		);

		$newPulse = $this->mondayGqlApi->createPulse($idBoard,$basicDataPulse);
		//$responseUpdatePulse = $this->mondayApi->updatePulse($idBoard,$newPulse->pulse->id,'status3','status',$data);
		$updatePulse = $this->mondayGqlApi->updatePulse($idBoard,$newPulse->data->create_item->id,'vendor2','Good People Spo',);
		$this->assertObjectHasAttribute('id',$updatePulse->data->change_simple_column_value);		
		/*
		$delPulse = $this->mondayApi->deletePulse($newPulse->pulse->id);
		$this->assertEquals('P120181251',$delPulse->name);
		*/

	}


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

     	\Artisan::call('migrate');
    }

}