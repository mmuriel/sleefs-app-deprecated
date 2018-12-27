<?php

namespace Sleefs\Helpers\Google\SpreadSheets;

class GoogleSpreadsheetGetWorkSheetIndex{




	public function getWSIndex($gWorkSheets,$workSheetName){

		
    	for($i = 0; $i < count($gWorkSheets);$i++){

    		if ($gWorkSheets[$i]->getTitle() == $workSheetName)
    			return $i;

    	}

    	return false;

	}


}