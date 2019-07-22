<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Models\Shiphero\PurchaseOrder;

class PurchaseOrderListView {

	private $po;

	public function __construct(\Sleefs\Models\Shiphero\PurchaseOrder $po){
		$this->po = $po;
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

		$htmlToRet = '
								<tr>
                                    <td>'.$this->po->po_id.'</td>
                                    <td>'.$this->po->created_at.'</td>
                                    <td>'.count($this->po->items).'</td>
                                    <td>
                                    	<a href="'.\App::make('url')->to('/pos/'.$this->po->po_id).'" target="_blank">
                                        Reporte
                                        </a>
                                    </td>
                                </tr>';

		return $htmlToRet;
	}


}