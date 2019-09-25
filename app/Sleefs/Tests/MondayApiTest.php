<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;


use \Sleefs\Helpers\MondayApi\MondayApi;

class MondayApiTest extends TestCase {

	public $urlEndPoint, $apiKey, $mondayApi;

	public function setUp()
    {
        parent::setUp();

        $this->urlEndPoint = env('MONDAY_BASEURL');
        $this->apiKey = env('MONDAY_APIKEY');
        $this->mondayApi = new MondayApi($this->urlEndPoint,$this->apiKey);
        //print_r($this->mondayApi);
        $this->prepareForTests();

    }
 

	public function testGetAllBoards(){

		$allBoards = $this->mondayApi->getAllBoards();
		//print_r($allBoards);
		$this->assertEquals(9,count($allBoards));
		$this->assertEquals('POs',$allBoards[0]->name);

	}


	public function testGetABoard(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$idBoard = 227352240;

		
		$board = $this->mondayApi->getBoard($idBoard);
		//print_r($board);
		$this->assertEquals('POSTest',$board->name);
		//$this->assertEquals('POs',$allBoards[0]->name);

	}


	public function testGetBoardPulses(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$idBoard = 230782591;
		$pulses = $this->mondayApi->getBoardPulses($idBoard,'page=1&per_page=25');
		$this->assertEquals(25,count($pulses));
		$this->assertEquals('P120181231',$pulses[0]->column_values[0]->name);

	}


	public function testGetPulse(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$pulseId = '322181434';
		$pulse = $this->mondayApi->getPulse($pulseId);
		//print_r($pulse);
		$this->assertEquals('1909-05',$pulse->name);
	}


	public function testGetPulseError(){

		//ID of board to test: https://sleefs.monday.com/boards/227352240/
		$pulseId = '81434';
		$pulse = $this->mondayApi->getPulse($pulseId);
		//print_r($pulse->error);
		$this->assertObjectHasAttribute('error',$pulse);
	}


	public function testAddPulseToBoard(){

		//ID of board to test: https://sleefs.monday.com/boards/230782591
		$idBoard = 230782591;
		$data = array(
			'pulse[name]' => 'P120181250',
			'board_id' => '230782591',
			'user_id' => '5277993',
		);
		$newPulse = $this->mondayApi->createPulse($idBoard,$data);
		$this->assertEquals('P120181250',$newPulse->pulse->name);
		$delPulse = $this->mondayApi->deletePulse($newPulse->pulse->id);
		$this->assertEquals('P120181250',$delPulse->name);
	}



	public function testGetBoardGroups(){
		//ID of board to test: https://sleefs.monday.com/boards/230782591
		$idBoard = 230782591;
		$boards = $this->mondayApi->getAllBoardGroups($idBoard);
		//print_r($boards);
		$this->assertEquals(8,count($boards));
	}


	public function testAddAndDeleteBoardGroup(){

		$idBoard = 230782591;
		$date = time();
		$groupTitle ="PO ".ucfirst(date("F",$date));
		$data = array(
			'board_id' => $idBoard,
			'title' => $groupTitle,
		);
		$newGroup = $this->mondayApi->addGroupToBoard($idBoard,$data);
		//Asserting the add action
		$this->assertEquals($groupTitle,$newGroup->title);
		$delResponse = $this->mondayApi->delBoardGroup($idBoard,$newGroup->id);
		//Asserting the delete action
		$this->assertEquals($newGroup->id,$delResponse[(count($delResponse) - 1)]->id);
		$this->assertEquals(1,$delResponse[(count($delResponse) - 1)]->archived);
		$this->assertEquals($newGroup->title,$delResponse[(count($delResponse) - 1)]->title);

	}

	
	public function testAddPulseToBoardAndModifyFields(){

		//ID of board to test: https://sleefs.monday.com/boards/230782591
		$idBoard = 230782591;
		$dataPulse = array(
			'pulse[name]' => 'P120181251',
			'board_id' => '230782591',
			'user_id' => '5277993',
		);
		$newPulse = $this->mondayApi->createPulse($idBoard,$dataPulse);
		$data = array('color_index' => '0',);
		$responseUpdatePulse = $this->mondayApi->updatePulse($idBoard,$newPulse->pulse->id,'status3','status',$data);
		$this->assertObjectHasAttribute('value',$responseUpdatePulse);
		$this->assertEquals(0,$responseUpdatePulse->value->index);
		$delPulse = $this->mondayApi->deletePulse($newPulse->pulse->id);
		$this->assertEquals('P120181251',$delPulse->name);

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