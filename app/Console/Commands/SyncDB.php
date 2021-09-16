<?php

namespace App\Console\Commands;

use App\Jobs\SyncDBJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la base de datos mediante la api VTEX';

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
     * @return int
     */
    public function handle()
    {
        try {
            //establezco el formato para el input de los parametros tipo fecha
            $dateFormat = 'd-m-Y';

            //solicito el input del filtro "fecha desde"
            $date1 = $this->ask('Ingrese el filtro "fecha desde" (dd-mm-aaaa)');
            //valido el input del filtro "fecha desde"
            $validaDate1=Carbon::createFromFormat($dateFormat, $date1);

            //solicito el input del filtro "fecha hasta"
            $date2 = $this->ask('Ingrese el filtro "fecha hasta" (dd-mm-aaaa)');
            //valido el input del filtro "fecha hasta"
            $validaDate2=Carbon::createFromFormat($dateFormat, $date2);

            //valido que el filtro "fecha hasta" no sea menor al filtro "fecha desde"
            if ($validaDate1>$validaDate2){
                $this->error('El filtro "fecha hasta" debe ser mayor o igual al filtro "fecha desde"');
                return;
            }
            //declaro el array de estados admitidos para una orden
            $estadosAdmitidos= array('waiting-for-sellers-confirmation',
                'payment-pending',
                'payment-approved',
                'ready-for-handling',
                'handling',
                'invoiced',
                'canceled',
                '');//para busqueda por todos los estados

            //solicito el input del filtro "estado"
            $estado = $this->ask('Ingrese el filtro "estado" (ej: "ready-for-handling"), o presione enter para omitir este filtro');

            //valido que el input "estado" se encuentre dentro de los estados admitidos
            if (in_array($estado,$estadosAdmitidos)==false){
                $this->error('El estado no esta dentro de los admitidos por la busqueda');
                echo ("Los estados admitidos son: \r\n");
                print_r($estadosAdmitidos);
                return;
            }
            //ejecuto la tarea de sincronizacion con la base
            dispatch(new SyncDBJob($date1,$date2,$estado));
        }catch (\Carbon\Exceptions\InvalidFormatException $e) {
            $this->error('El filtro no coincide con el formato establecido');
        }
    }
}
