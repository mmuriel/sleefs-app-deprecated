<?php

namespace Sleefs\Helpers\Monday;

class MondayGroupChecker{

	public function getCorrectGroupName ($poDate){

		$monthInName = substr($poDate,5,2);
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

		$yearInName = substr($poDate,0,4);
		//$yearInName = 2000 + ((int)$yearInName);
		//$groupName .= ' '.date("Y");


		$groupName .= ' '.$yearInName;
		//echo $yearInName."\n";
		return $groupName;

	}

	/**
	*	Recupera el grupo al que pertenece (o debería pertenecer a partir del Pulse.name) 
	*	un pulse.
	*
	*	Recupera a partir del API de monday.com el grupo al que pertenece un pulse,
	*	se utilize el atributo Pulse.name para deducir el nombre del grupo en el tablero.
	*
	*	@param 	string $poDate Fecha de creación de la PO en formato YYYY-MM-DD
	*	@param 	integer $boardId ID en el sistema de monday.com del board sobre el que se
	*			realizará la búsqueda.
	*	@param 	\Sleefs\Helpers\MondayApi\MondayGqlApi $mondayApi Objeto que sirve de interfaz
	*			para con el API GraphQL de monday.com
	*	@return	stdClass $group Un objeto tipo stdClass que representa un group, tiene la
	*			siguiente estructura:

				{
					"color": "#037f4c",
					"id": "po_october_2020",
					"position": "65536.0",
					"title": "PO October 2020"
				}
	*
	*
	*/
	public function getGroup($poDate,$boardId,\Sleefs\Helpers\MondayApi\MondayGqlApi $mondayApi){

		$groupName = $this->getCorrectGroupName($poDate);
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