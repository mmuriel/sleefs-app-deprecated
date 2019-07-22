<?php

namespace Sleefs\Models\Shopify;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    //
    protected $table = 'variants';

    public function product(){

    	return $this->belongsTo('Sleefs\Models\Shopify\Product','idproduct');

    }
}
