<?php

namespace Sleefs\Helpers\Shopify;

use \Sleefs\Helpers\Misc\Response;

class PdfTemplateSeeker{

	/**
    *
    *    This method look for a PDF template file (to print sticker) in the path and file name
    *    @param String $pathToFile
    *    @param String $fileName (without file extension)
    *
    *    @return Sleefs\Helpers\Misc\Response $resp
    *
    */
    public function seekForPdfTemplate ($pathToFile,$fileName): \Sleefs\Helpers\Misc\Response 
    {

    	$resp = new \Sleefs\Helpers\Misc\Response();
    	if (file_exists($pathToFile.$fileName.".pdf"))
    	{
    		$resp->value = true;
    		$resp->status = 'no blank';
    		$resp->notes = $pathToFile.$fileName.".pdf";
    	}
    	elseif (file_exists($pathToFile.$fileName." __blank.pdf"))
    	{
    		$resp->value = true;
    		$resp->status = 'blank';
    		$resp->notes = $pathToFile.$fileName." __blank.pdf";
    	}
    	else
    	{
    		$resp->value = false;
    		$resp->status = '';
    	}

    	return $resp;

    }

}