<?php
namespace Sleefs\Helpers\Misc\Interfaces;
use \Illuminate\Database\Eloquent\Model;
/**
 * 
 * Esta interfaz define un contrato para comparar a través de los datos que deben
 * compartir, si un Modelo de datos local está sincronizado a través de los datos
 * con un modelo de datos remoto.
 * 
*/
interface ISyncedDataChecker{

	public function validateSyncedData(\Illuminate\Database\Eloquent\Model $localModel, \stdClass $remoteModel);

}