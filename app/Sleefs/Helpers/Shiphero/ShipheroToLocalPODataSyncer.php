<?php
namespace Sleefs\Helpers\Shiphero;

use Sleefs\Helpers\Misc\interfaces\IRemoteToLocalDataSyncer;


class ShipheroToLocalPODataSyncer implements IRemoteToLocalDataSyncer{

	public function syncData($localPo,$remotePo):array{

		try{
			//1. It syncs fulfillment status
			$localPo->fulfillment_status = $remotePo->fulfillment_status;

			//2. It syncs PO's Items 
			foreach($localPo->items as $localItem){

				foreach ($remotePo->line_items->edges as $remoteItem){
					if ($localItem->shid == $remoteItem->node->id){

						$localItem->quantity = $remoteItem->node->quantity;
						$localItem->quantity_received = $remoteItem->node->quantity_received;
						$localItem->qty_pending = 0;
						$localItem->save();
						break;
					}
				}

			}
			$localPo->save();

		}
		catch (\Exception $e){

			return array(true,$e->getMessage());

		}
		return array(false,$localPo);

	}

}