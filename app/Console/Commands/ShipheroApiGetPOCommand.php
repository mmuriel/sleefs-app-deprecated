<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \mdeschermeier\shiphero\Shiphero;

class ShipheroApiGetPOCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShipheroAPI:getpo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It gets a PO from Shiphero system';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        Shiphero::setKey('8c072f53ec41629ee14c35dd313a684514453f31');
        $po = Shiphero::getPO(167);
        var_dump($po);
        echo "\n====================\n";
        echo json_encode($po);
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.log");
        $clogger->writeToLog (json_encode($po),"INFO");

        echo "Hola mundo comandos en laravel 5.4\n";
    }
}
