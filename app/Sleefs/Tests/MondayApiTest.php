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

        $this->urlEndPoint = 'api.monday.com/v1/';
        $this->apiKey = 'e4beacc70ba73f31032053947e1709a3';
        $this->mondayApi = new MondayApi($this->urlEndPoint,$this->apiKey);
        //print_r($this->mondayApi);
        $this->prepareForTests();

    }
 

	public function testGetAllBoards(){

		$allBoards = $this->mondayApi->getAllBoards();
		//print_r($allBoards);
		$this->assertEquals(3,count($allBoards));
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
		$idBoard = 227352240;


		$pulses = $this->mondayApi->getBoardPulses($idBoard);
		//print_r($pulses);
		$this->assertEquals(2,count($pulses));
		$this->assertEquals('P120181219',$pulses[0]->column_values[0]->name);

	}


	public function testAddPulseToBoard(){

		//ID of board to test: https://sleefs.monday.com/boards/230782591
		$idBoard = 230782591;
		$idUser = 5277993;

		$pulses = $this->mondayApi->getBoardPulses($idBoard);
		//print_r($pulses);
		$this->assertEquals(4,count($pulses));
		$this->assertEquals('P120181220',$pulses[0]->column_values[0]->name);

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