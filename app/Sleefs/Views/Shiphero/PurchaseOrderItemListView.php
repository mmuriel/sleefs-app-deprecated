<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Models\Shiphero\PurchaseOrderItem;

class PurchaseOrderItemListView {

	private $poItem;

	public function __construct(\Sleefs\Models\Shiphero\PurchaseOrderItem $poItem){
		$this->poItem = $poItem;
		setlocale(LC_MONETARY, 'en_US');
	}

	/*
	public function defineIfReported(){

		//console.log(this.props.reportesIndexes[]);
		if (typeof this.props.reportesIndexes[this.props.prg.id] != 'undefined' && this.props.reportesIndexes[this.props.prg.id] != null){
			this.isReported = true;
			this.reporte = this.props.state.reportes[this.props.reportesIndexes[this.props.prg.id][1]];
		}
		

	}
	*/



	public function render(){

		
		$imageObject = \DB::table('product_images')
						->leftJoin('products', 'product_images.idproducto', '=', 'products.id')
						->leftJoin('variants','products.id','=','variants.idproduct')
						->select('product_images.url')
						->where('variants.sku','=',$this->poItem->sku)->get();

		if (count($imageObject)>0){
			//var_dump($imageObject);
			$imageUrl = $imageObject->get(0)->url;
			$styleObj = "width: 150px;";
		}
		else{
			$imageUrl = \App::make('url')->to('/').'/imgs/no-image-2.png';
			$styleObj = "width: 50px;";
		}


		$htmlToRet = '
								<tr>
                                    <td>'.$this->poItem->name.'</td>
                                    <td>'.$this->poItem->sku.'</td>
                                    <td>'.$this->poItem->quantity.'</td>
                                    <td>'.money_format('%i',$this->poItem->price).'</td>
                                    <td>$'.money_format('%i',($this->poItem->price * $this->poItem->quantity)).'</td>
                                    <td>
                                    	<img src="'.$imageUrl.'" style="'.$styleObj.'"/>
                                    </td>
                                </tr>';

		return $htmlToRet;
	}


}