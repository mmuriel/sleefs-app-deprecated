<?php

namespace Sleefs\Helpers\Monday;

class MondayVendorValidator{

	private $validVendors;

	public function __construct($validVendors){
		$this->validVendors = $validVendors;
	}

	public function validateVendor($vendorName){

		$isValid = false;
		for ($i=0; $i < count($this->validVendors);$i++){
			if ($this->validVendors[$i]==$vendorName){
				$isValid = true;
			}
		}
		return $isValid;
	}

}