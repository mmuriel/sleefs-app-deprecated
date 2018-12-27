<?php

namespace Sleefs\Helpers\Google\SpreadSheets;

class GoogleSpreadsheetFileLocker{
	
	public function lockFile($gSpreadSheet,$ctrlWsIndex){

        try{
    		$worksheets = $gSpreadSheet->getWorksheetFeed()->getEntries();
            $worksheet = $worksheets[$ctrlWsIndex];
            $cellFeed = $worksheet->getCellFeed();
            $cell = $cellFeed->getCell(1,1);
            $cell->update('locked');
            return true;
        }
        catch (\Exception $e){

            //echo $e->getMessage()."mmmm\n";
            return false;

        }

	}

}