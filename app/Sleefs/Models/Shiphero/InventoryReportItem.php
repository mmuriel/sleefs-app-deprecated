<?php

namespace Sleefs\Models\Shiphero;

use Illuminate\Database\Eloquent\Model;

class InventoryReportItem extends Model{

	protected $table = 'sh_inventoryreport_items';

	public function InventoryReport(){

    	return $this->belongsTo('Sleefs\Models\Shiphero\InventoryReport','idreport','id');

    }

}