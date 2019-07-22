<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Views\Shiphero\PurchaseOrderUpdateItemView;
use \Sleefs\Models\Shiphero\PurchaseOrder;

class PurchaseOrderUpdateView {

	private $poUpdate,$poUpdateItems;

	public function __construct(\Sleefs\Models\Shiphero\PurchaseOrderUpdate $poUpdate){
		$this->poUpdate = $poUpdate;

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


        $po = PurchaseOrder::find($this->poUpdate->idpo);

        $htmlPoItems = '';
        if (count($this->poUpdate->updateItems)>0)
            foreach ($this->poUpdate->updateItems as $poItem){

                $tmpRenderItem = new PurchaseOrderUpdateItemView($poItem);
                $htmlPoItems .= $tmpRenderItem->render();

            }
        else
            $htmlPoItems = '<tr><td colspan="5">No update items found</td></tr>';

		$htmlToRet = '
								<tr class="update__tr__'.$this->poUpdate->id.'">
                                    <td><input type="checkbox" name="" id="" class="update_'.$this->poUpdate->id.' updateitems__selector" data-poupdate="'.$this->poUpdate->id.'"/></td>
                                    <td>'.$po->po_id.'</td>
                                    <td>'.$this->poUpdate->created_at.'</td>
                                    <td>'.count($this->poUpdate->updateItems).'</td>
                                    <td>
                                        <a href="#" class="poupdate__items__displayer" data-poupdate="'.$this->poUpdate->id.'" data-status="open">
                                            <span class="icon-arrow-down"> </span>
                                        </a>
                                    </td>
                                </tr>
                                <tr class="update__tr__items__'.$this->poUpdate->id.'">
                                    <td colspan="5">
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>
                                                        
                                                    </th>
                                                    <th>
                                                        SKU
                                                    </th>
                                                    <th>
                                                        Total Recibido
                                                    </th>
                                                    <th>
                                                        Posición
                                                    </th>
                                                    <th>
                                                        Saldo anterior
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                '.$htmlPoItems.'
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>';

		return $htmlToRet;
	}


}