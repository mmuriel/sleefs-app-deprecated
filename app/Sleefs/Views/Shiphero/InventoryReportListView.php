<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Models\Shiphero\InventoryReport;

class InventoryReportListView {

	private $report;

	public function __construct(\Sleefs\Models\Shiphero\InventoryReport $report){
		$this->report = $report;
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
                                    <td>
                                    	<a href="'.env("APP_URL").'/inventoryreport/'.$this->report->id.'" target="_blank">
                                    	'.substr($this->report->created_at,0,10).'
                                    	</a>
                                    </td>
                                </tr>';

		return $htmlToRet;
	}


}