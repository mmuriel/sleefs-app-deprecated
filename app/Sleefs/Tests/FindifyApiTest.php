<?php

namespace Sleefs\Test;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;

use Sleefs\Helpers\FindifyAPI\Findify;

class FindifyApiTest extends TestCase {

	//public $prd,$var1,$var2;

	public function setUp(){
        parent::setUp();
        $this->prepareForTests();
    }

    /* TESTs */
    public function testLoginToFindifyApi(){

    	$findifyApi = new Findify('https://admin.findify.io/v1/');
    	$loginResult = $findifyApi->login('admin@sleefs.com','Sleefs--5931');
    	$this->assertTrue($loginResult->value);
    	$this->assertEquals('admin@sleefs.com',$loginResult->notes->user->email);

    }


    public function testLoginToFindifyApiError(){

    	$findifyApi = new Findify('https://admin.findify.io/v1/');
    	$loginResult = $findifyApi->login('admin@sleefs.com','Sleefs--5931-MMMM');
    	$this->assertFalse($loginResult->value);
    	$this->assertEquals('Username or password not correct',$loginResult->notes->error->message);

    }


    public function testGetAllCollections(){

    	$findifyApi = new Findify('https://admin.findify.io/v1/');
    	$loginResult = $findifyApi->login('admin@sleefs.com','Sleefs--5931');
    	$collections = $findifyApi->getAllCollections(250,250);
    	$this->assertTrue(is_array($collections));
    	$this->assertEquals(56233,$collections[0]->id,"Se ha cambiado el ID de la colección 'NEW', hay que cambiar ese código");

    }


    public function testSearchForNewCollection(){

    	$findifyApi = new Findify('https://admin.findify.io/v1/');
    	$loginResult = $findifyApi->login('admin@sleefs.com','Sleefs--5931');
    	$collections = $findifyApi->getAllCollections(250,250);
    	$newCollection = new \stdClass();
    	foreach ($collections as $rawCollection){
    		if (preg_match("/\/collections\/new$/",$rawCollection->slot)){
    			 $newCollection = $rawCollection;
    			 break;
    		}
    	}
    	//echo "\nLa clase new es la siguiente:\n";
    	$newFilter = new \stdClass();
    	$newFilter->value = 'MMMMTESTING';
    	array_push($newCollection->query->filters[0]->values,$newFilter);
    	//print_r($newCollection);
    	//return 1;
    	$response = $findifyApi->updateCollection($newCollection,250,250);
        $response->id = $newCollection->id;
    	$indexLastAddedItem = count($response->query->filters[0]->values);
    	$indexLastAddedItem--;
    	$this->assertEquals('MMMMTESTING',$response->query->filters[0]->values[$indexLastAddedItem]->value);
    	//Elimina el ultimo registro
    	array_pop($response->query->filters[0]->values);
    	$response = $findifyApi->updateCollection($response,250,250);
    	$this->assertEquals($indexLastAddedItem,count($response->query->filters[0]->values));
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
    private function prepareForTests(){
     	// \Artisan::call('migrate');
    }

}