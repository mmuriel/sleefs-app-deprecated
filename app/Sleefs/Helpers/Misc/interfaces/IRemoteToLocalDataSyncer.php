<?php
namespace Sleefs\Helpers\Misc\Interfaces;
use \Illuminate\Database\Eloquent\Model;
/**
 * 
 * Esta interfaz define un contrato para sincronizar los datos (en caso de discrepancia)
 * entre un modelo local y un servicio remoto, que representan la misma entidad. Por ejemplo
 * las POs en la plataforma Shiphero.com y las mismas POs en el sistema de sincronización
 * de datos para Sleefs (sleefs-2.sientifica.com)
 * 
*/
interface IRemoteToLocalDataSyncer{

	public function syncData(\Illuminate\Database\Eloquent\Model $localModel, \stdClass $remoteModel):array;

}