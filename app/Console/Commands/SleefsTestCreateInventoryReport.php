<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SleefsTestCreateInventoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SleefsTestCreateInventoryReport:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para testear la generación de un reporte de inventario de manera asíncrona, generando un fork de otro script php mediante el llamado de comando del sistema, delegando la tarea a otro proceso separado al proceso principal que ejecuta la tarea.';

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
        exec("php /home/admin/app/artisan inventoryreport:create > /dev/null 2>&1 & echo $!");
    }
}
