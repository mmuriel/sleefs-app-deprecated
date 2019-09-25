<?php

namespace Sleefs\Helpers\Monday;

class MondayGroupChecker{

	public function getCorrectGroupName ($pulseName){

		$monthInName = substr($pulseName,2,2);
		$groupName = 'PO ';
		switch($monthInName){
			case '01':
				$groupName .= 'January';
				break;
			case '02':
				$groupName .= 'February';
				break;
			case '03':
				$groupName .= 'March';
				break;
			case '04':
				$groupName .= 'April';
				break;
			case '05':
				$groupName .= 'May';
				break;
			case '06':
				$groupName .= 'June';
				break;
			case '07':
				$groupName .= 'July';
				break;
			case '08':
				$groupName .= 'August';
				break;
			case '09':
				$groupName .= 'September';
				break;
			case '10':
				$groupName .= 'October';
				break;
			case '11':
				$groupName .= 'November';
				break;
			case '12':
				$groupName .= 'December';
				break;
		}

		$groupName .= ' '.date("Y");
		return $groupName;

	}

	public function getGroup($pulseName,$boardId,\Sleefs\Helpers\MondayApi\MondayApi $mondayApi){

		$groupName = $this->getCorrectGroupName($pulseName);
		$groups = $mondayApi->getAllBoardGroups($boardId);
		$group = '';
		for($i=0;$i<count($groups);$i++){
			if (preg_match('/^'.$groupName.'/i',trim($groups[$i]->title))){
				$group = $groups[$i];
				break;
			}
		}

		if ($group==''){
			return null;
		}
		else{
			return $group;
		}
	}

}