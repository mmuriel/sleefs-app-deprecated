<?php

namespace Sleefs\Helpers\Shiphero;
use Sleefs\Models\Shiphero\Vendor;

class ShipheroVendorsDiffChecker {


    /**
    *
    *    This method checks for non already vendors saved in the local database
    *    @param Array $apiData An array of objects with next format:
    *                   [(
    *                        [node] => stdClass Object
    *                            (
    *                                [name] => FUJIAN QUANZHOU HV
    *                                [legacy_id] => 337748
    *                                [id] => VmVuZG9yOjMzNzc0OA==
    *                                [email] => ruby@hvtex.com
    *                                [account_number] => 
    *                            )
    *                    )]
    *
    *    @return Array $vendorsToSave An array of object like next:
    *                   [(
    *                        [node] => stdClass Object
    *                            (
    *                                [name] => FUJIAN QUANZHOU HV
    *                                [legacy_id] => 337748
    *                                [id] => VmVuZG9yOjMzNzc0OA==
    *                                [email] => ruby@hvtex.com
    *                                [account_number] => 
    *                            )
    *                    )]
    *
    */
    public function checkDiff($apiData){

        $arrShipheroVendorId = array();
        $localVendors = Vendor::all();
        $vendorsToSave = array();

        if ($localVendors->isEmpty()){
            return $apiData;
        }

        foreach ($apiData as $apiVendor){
            if ($localVendors->whereIn('idsp',$apiVendor->node->id)->isEmpty()){
                array_push($vendorsToSave,$apiVendor);                
            }
        }
        return $vendorsToSave;
    }
}

