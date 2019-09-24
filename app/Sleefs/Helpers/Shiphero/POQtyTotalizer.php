<?php

namespace Sleefs\Helpers\Shiphero;
use Sleefs\Models\Shiphero\PurchaseOrderItem;

class POQtyTotalizer {

    public function getTotalItems($poId,$type){
        $poItems = PurchaseOrderItem::whereRaw("idpo='".$poId."'")->get();
        $total = 0;
        $received = 0;
        foreach($poItems as $item){
            $total = $total + $item->quantity;
            $received = $received + $item->quantity_received;
        }

        switch($type){
            case 'total':
                return $total;
                break;
            case 'received':
                return $received;
                break;
        }

    }

}

