<?php

namespace Sleefs\Helpers\Google\SpreadSheets;

class GoogleSpreadsheetFileUnLocker{
	
	public function unLockFile($gSpreadSheet,$ctrlWsIndex){

        try{
    		$worksheets = $gSpreadSheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[$ctrlWsIndex];
            $cellFeed = $worksheet->getCellFeed();
            $cell = $cellFeed->getCell(1,1);
            $cell->update('open');
            return true;
        }
        catch (\Exception $e){

            //echo $e->getMessage()."mmmm\n";
            return false;

        }

	}

}