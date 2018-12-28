<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Models\Shiphero\InventoryReport;

class InventoryReportItemListView {

	private $irItem;

	public function __construct(\Sleefs\Models\Shiphero\InventoryReportItem $irItem){
		$this->irItem = $irItem;
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
                                    <td>'.$this->irItem->label.'</td>
                                    <td>'.$this->irItem->total_inventory.'</td>
                                    <td>'.$this->irItem->total_on_order.'</td>
                                </tr>';

		return $htmlToRet;
	}


}