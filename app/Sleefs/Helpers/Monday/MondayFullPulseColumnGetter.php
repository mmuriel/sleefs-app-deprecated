<?php

namespace Sleefs\Helpers\Monday;


/* This class returns the value of a column in an object like this:

[pulse] => stdClass Object
        (
            [url] => https://sleefs.monday.com/projects/322181434
            [id] => 322181434
            [name] => 1909-05
            [updates_count] => 0
            [board_id] => 322181342
            [created_at] => 2019-09-12T16:22:17Z
            [updated_at] => 2019-09-19T00:19:57Z
        )

    [board_meta] => stdClass Object
        (
            [position] => 393216
            [group_id] => po
        )

    [column_values] => Array
        (
            [0] => stdClass Object
                (
                    [cid] => name
                    [title] => Name
                    [name] => 1909-05
                )

            [1] => stdClass Object
                (
                    [cid] => title6
                    [title] => Title
                    [value] => sw1759
                )

            [2] => stdClass Object
                (
                    [cid] => vendor2
                    [title] => Vendor
                    [value] => 
                )

            [3] => stdClass Object
                (
                    [cid] => created_date8
                    [title] => Created Date
                    [value] => 2019-09-25
                )

            [4] => stdClass Object
                (
                    [cid] => expected_date3
                    [title] => Expected Date
                    [value] => 2019-09-29
                )

            [5] => stdClass Object
                (
                    [cid] => pay
                    [title] => Pay
                    [value] => 
                )

            [6] => stdClass Object
                (
                    [cid] => received
                    [title] => Received
                    [value] => stdClass Object
                        (
                            [index] => 5
                            [changed_at] => 2019-09-18T22:11:40.122Z
                            [update_id] => 
                        )

                )

            [7] => stdClass Object
                (
                    [cid] => total_cost0
                    [title] => Total Cost
                    [value] => 67.50
                )

        )

)
*/

class MondayFullPulseColumnGetter{

	public function getValue ($idField,\stdClass $fullPulse){

		$valueToRet = null;
		for ($i=0;$i<count($fullPulse->column_values);$i++){

			if ($fullPulse->column_values[$i]->cid == $idField){

				$typeColumn = $this->getColumnType($fullPulse->column_values[$i]);
				switch($typeColumn){
					case 'name':
						return $fullPulse->column_values[$i]->name;
						break;
					case 'status':
						return $fullPulse->column_values[$i]->value->index;
						break;
					case 'other':
						return $fullPulse->column_values[$i]->value;
						break;
				}
				break;
			}

		}
		return $valueToRet;
	}


	private function getColumnType($valueField){

		if(isset($valueField->name)){
			return 'name';
		}

		if (isset($valueField->value)){

			if (gettype($valueField->value)=='object'){
				return 'status';
			}
			else{
				return 'other';
			}

		}
		else{
			return null;
		}

	}

}