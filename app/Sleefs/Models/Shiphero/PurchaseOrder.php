<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;
use Sleefs\Models\Monday\Pulse;

Class PurchaseOrder extends Model{

	protected $table = 'sh_purchaseorders';

	public function items(){

    	return $this->hasMany('Sleefs\Models\Shiphero\PurchaseOrderItem','idpo','id');

    }


    public function pulse(){
    	return $this->hasOne('Sleefs\Models\Monday\Pulse','idpo','id');
    }

}