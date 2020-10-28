<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use \Sleefs\Helpers\Shiphero\ShipheroDailyInventoryReport;

class InventoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventoryreport:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It creates "a picture" of the inventory state, including products in orders by product type';

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
        $clogger = new \Sleefs\Helpers\CustomLogger("sleefs.inventoryreport.log");
        

        $reportCreator = new ShipheroDailyInventoryReport();
        $report = $reportCreator->createReport(['graphqlUrl'=>'https://public-api.shiphero.com/graphql','authUrl'=>'https://public-api.shiphero.com/auth','qtyProducts'=>985,'tries' => 150]);

        echo "\nSe ha creado un nuevo reporte con ID ".$report->id." para la fecha: ".$report->created_at."\n";
        $clogger->writeToLog ("Se ha creado un nuevo reporte con ID ".$report->id." para la fecha: ".$report->created_at,"INFO");
    }
}
