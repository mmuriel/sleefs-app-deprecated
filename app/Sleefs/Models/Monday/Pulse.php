<?php

namespace Sleefs\Models\Monday;

use Illuminate\Database\Eloquent\Model;
use Sleefs\Models\Shiphero\PurchaseOrder;

class Pulse extends Model{

	protected $table = 'mon_pulses';

	public function po(){
        return $this->belongsTo('Sleefs\Models\Shiphero\PurchaseOrder','idpo');
    }


}