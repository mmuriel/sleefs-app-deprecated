<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;

Class PurchaseOrderUpdateItem extends Model{

	protected $table = 'sh_purchaseorders_updates_items';

	public function poUpdate(){

    	return $this->belongsTo('Sleefs\Models\Shiphero\PurchaseOrderUpdate','idpoupdate');

    }

}