<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;

class InventoryReport extends Model{

	protected $table = 'sh_inventoryreports';

	public function inventoryReportItems(){

    	return $this->hasMany('Sleefs\Models\Shiphero\InventoryReportItem','idreporte','id');

    }

}