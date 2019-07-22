<?php
namespace Sleefs\Views\Shiphero;

class PurchaseOrderUpdateItemView {

	private $poUpdateItem;

	public function __construct(\Sleefs\Models\Shiphero\PurchaseOrderUpdateItem $poUpdateItem){
		$this->poUpdateItem = $poUpdateItem;
	}

	public function render(){

		$htmlToRet = '
												<tr>
                                                    <td>
                                                        <input type="checkbox" name="poupdateitems[]" id="" class="itemUpdateItemInput itemUpdate_'.$this->poUpdateItem->idpoupdate.'" data-idpoupdateitem="'.$this->poUpdateItem->id.'" value="'.$this->poUpdateItem->id.'"/>
                                                     </td>
                                                    <td>
                                                        '.$this->poUpdateItem->sku.'
                                                    </td>
                                                    <td>
                                                        '.$this->poUpdateItem->quantity.'
                                                    </td>
                                                    <td>
                                                        '.$this->poUpdateItem->position.'
                                                    </td>
                                                    <td>
                                                        '.$this->poUpdateItem->qty_before.'
                                                    </td>
												</tr>';

		return $htmlToRet;
	}


}