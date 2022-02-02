<?php
namespace Sleefs\Helpers\Shiphero;

use Sleefs\Helpers\Misc\Interfaces\ISyncedDataChecker;


class ShipheroFulfillmentStatusSyncedDataChecker implements ISyncedDataChecker{

	public function validateSyncedData (\Illuminate\Database\Eloquent\Model $localPo, \stdClass $remotePo){

		if ($localPo->fulfillment_status == $remotePo->fulfillment_status)
			return true;
		else
			return false;
	}

}