<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;
use Sleefs\Models\Shiphero\PurchaseOrder;

Class PurchaseOrderItem extends Model {

	protected $table = 'sh_purchaseorder_items';

	public function po(){

    	return $this->belongsTo('Sleefs\Models\Shiphero\PurchaseOrder','idpo','id');

    }

}