<?php

namespace Sleefs\Helpers\Monday;


/* This class returns the value of a column in an object like this:

[pulse] => stdClass Object
(
    [id] => 792303653
    [board] => stdClass Object
        (
            [id] => 670700889
            [name] => CPPendingPOs-MMA-DEV
        )

    [group] => stdClass Object
        (
            [id] => po_october_2020
            [title] => POOctober2020
        )

    [name] => 2010-04
    [state] => active
    [column_values] => Array
        (
            [0] => stdClass Object
                (
                    [id] => title6
                    [text] => USAReOrder
                    [title] => Title
                    [type] => text
                    [value] => "USAReOrder"
                )

            [1] => stdClass Object
                (
                    [id] => vendor2
                    [text] => GoodPeopleSports
                    [title] => Vendor
                    [type] => text
                    [value] => "GoodPeopleSports"
                )

            [2] => stdClass Object
                (
                    [id] => created_date8
                    [text] => 2020-10-07
                    [title] => CreatedDate
                    [type] => date
                    [value] => {"date":"2020-10-07","icon":""}
                )

            [3] => stdClass Object
                (
                    [id] => expected_date3
                    [text] => 2020-10-26
                    [title] => ExpectedDate
                    [type] => date
                    [value] => {"date":"2020-10-26","icon":""}
                )

            [4] => stdClass Object
                (
                    [id] => pay
                    [text] => Paid
                    [title] => Pay
                    [type] => color
                    [value] => {"index":7,"post_id":null,"changed_at":"2020-10-21T04:35:17.182Z"}
                )

            [5] => stdClass Object
                (
                    [id] => received
                    [text] => No
                    [title] => Received
                    [type] => color
                    [value] => {"index":2,"post_id":null}
                )

            [6] => stdClass Object
                (
                    [id] => total_cost0
                    [text] => 3349.5
                    [title] => TotalCost
                    [type] => numeric
                    [value] => "3349.5"
                )

        )

)
*/

class MondayFullPulseColumnGetter{

	public function getValue ($idField,\stdClass $fullPulse){

		$valueToRet = null;

        if ($idField == 'name'){
            return $fullPulse->name;
        }

		for ($i=0;$i<count($fullPulse->column_values);$i++){

			if ($fullPulse->column_values[$i]->id == $idField){

				switch($fullPulse->column_values[$i]->type){
					case 'color':

                        if ($fullPulse->column_values[$i]->value !='' && $fullPulse->column_values[$i]->value != null)
                        {
                            $tmpVal = json_decode($fullPulse->column_values[$i]->value); 
                            return $tmpVal->index;
                        }
                        else
                        {
                            return $fullPulse->column_values[$i]->text;
                        }
						
						break;
					default:
						return $fullPulse->column_values[$i]->text;
						break;
				}
				break;
			}

		}
		return $valueToRet;
	}

}