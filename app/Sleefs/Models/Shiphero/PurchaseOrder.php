<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;

Class PurchaseOrder extends Model{

	protected $table = 'sh_purchaseorders';

	public function items(){

    	return $this->hasMany('Sleefs\Models\Shiphero\PurchaseOrderItem','idpo','id');

    }

}