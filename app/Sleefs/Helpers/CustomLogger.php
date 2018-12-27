<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Sleefs\Helpers;
/**
 * Description of newPHPClass
 *
 * @author @maomuriel
 * mauricio.muriel@calitek.net
 */
class CustomLogger {

    //put your code here
    private $logName = '';

    public function __construct($logName){

    	$this->logName = $logName;

    }

	public function writeToLog ($msg,$type_message){

        $log = new \Monolog\Logger($this->logName);

        switch ($type_message){

            case 'WARNING': $log->pushHandler(new \Monolog\Handler\StreamHandler(base_path().'/app.log', \Monolog\Logger::WARNING));
                            $log->addWarning($msg);
                            break;
            case 'ERROR':   
                            $log->pushHandler(new \Monolog\Handler\StreamHandler(base_path().'/app.log', \Monolog\Logger::ERROR));
                            $log->addError($msg);
                            break;
            case 'INFO':   
                            $log->pushHandler(new \Monolog\Handler\StreamHandler(base_path().'/app.log', \Monolog\Logger::INFO));
                            $log->addInfo($msg);
                            break;

        }

    }

}
