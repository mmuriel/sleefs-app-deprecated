<?php

namespace Sleefs\Helpers\Monday;

class MondayPulseNameExtractor{

	public function extractPulseName($poNumber,$vendor,$mondayValidVendors){

		if (in_array($vendor,$mondayValidVendors)){
			$poNumber = preg_replace("/^(PO\ |PI\ ){0,1}/","",$poNumber);
			return substr($poNumber,0,7);
		}

		if (preg_match("/(PO\ [\-A-Z0-9\/]{2,40}|PI\ [\-A-Z0-9\/]{2,40}|[\-A-Z0-9\/]{4,40})/",$poNumber,$matches)){
			return $matches[1];
		}
		return false;

	}

}