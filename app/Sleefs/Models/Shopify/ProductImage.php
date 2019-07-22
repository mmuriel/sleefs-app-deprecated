<?php

namespace Sleefs\Models\Shopify;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    //
    protected $table = 'product_images';


    public function product(){

    	return $this->belongsTo('Sleefs\Models\Shopify\Product','idproducto');

    }
}
