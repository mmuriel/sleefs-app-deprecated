<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;

Class PurchaseOrderUpdate extends Model{

	protected $table = 'sh_purchaseorders_updates';

	public function updateItems(){

    	return $this->hasMany('Sleefs\Models\Shiphero\PurchaseOrderUpdateItem','idpoupdate','id');

    }

}