<?php

namespace Sleefs\Helpers\Shopify;

use Sleefs\Helpers\Misc\Response;

class ProductNameToDirectoryChecker{

	/**
    *
    *    This method validates if a directory of product name is already created.
    *    @param String $directoryPath
    *
    *    @return Sleefs\Helpers\Misc\Response $resp
    *
    */
    public function isDirectoryAlreadyCreated ($directoryPath){

        $resp = new Response();
        if (is_dir($directoryPath)){
            $resp->value = true;
            $resp->status = true;
            $resp->notes = 'La carpeta ('.$directoryPath.') ya existe en el sistema de archivos';
        }
        else{
            $resp->value = false;
            $resp->status = false;
            $resp->notes = 'La carpeta ('.$directoryPath.') no existe en el sistema de archivos';
        }
        return $resp;
    }

}