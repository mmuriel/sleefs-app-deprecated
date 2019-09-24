<?php

namespace Sleefs\Helpers\Monday;

class MondayPulseNameExtractor{

	public function extractPulseName($poNumber){

		return substr($poNumber,0,7);
	}

}