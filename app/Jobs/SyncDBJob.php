<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Item;
use App\Models\Order;
use App\Models\PaymentSystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncDBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $date1;
    protected $date2;
    protected $estado;

    public function __construct($date1, $date2, $estado)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->estado = $estado;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //recibo la fecha del constructor  y creo un objeto DateTime
        $date1 = new \DateTime($this->date1);
        //le doy el formato requerido por la API
        $fromDate =  $date1->format('Y-m-d\TH:i:s.v\Z');

        $date2 = new \DateTime($this->date2);
        $toDate = $date2->format('Y-m-d\TH:i:s.v\Z');

        //recibo el estado del constructor y lo asigno a una variable
        $status=$this->estado;

        //obtengo una lista con los orderId, segun los parametros establecidos
        $ordersIdList = $this->getOrdersIdList($toDate, $fromDate,$status);
        echo ("Las ordenes recuperadas para los parametros ingresados son: \r\n");
        print_r($ordersIdList);
        echo ("Sincronizando con la base de datos... \r\n");
        //llamo al proceso de sincronizacion de la base, pasando como parametro la lista con los orderId
        $this->syncDB($ordersIdList);
        echo ('Proceso ejecutado de forma correcta.');
    }

    public function syncDB($ordersIdList){
        foreach ($ordersIdList as  $orderId){
            //para cada uno de los orderId de la lista obtengo un objeto Orden llamando a la funcion getOrder
            $orden=json_decode($this->getOrder($orderId) );
            //creo un Cliente o lo actualizo si este ya existiese.
            $client = Client::updateOrCreate(
                ['client_id'=>$orden->clientProfileData->userProfileId],
                [
                    'nombre' => $orden->clientProfileData->firstName,
                    'apellido' => $orden->clientProfileData->lastName,
                    'email' => $orden->clientProfileData->email
                ]
            );
            //creo un Sistema de Pago o lo actualizo si este ya existiese.
            $paymentSystem = PaymentSystem::updateOrCreate(
                ['payment_system_id'=>$orden->paymentData->transactions[0]->payments[0]->paymentSystem],
                [
                    'nombre' => $orden->paymentData->transactions[0]->payments[0]->paymentSystemName
                ]
            );
            //creo una Orden o la actualizo si esta ya existiese.
            $order = Order::updateOrCreate(
                ['order_id'=>$orden->orderId],
                [
                    'precio_total' => $orden->value,
                    'procesada' => false,
                    'client_id' => $client->id,
                    'payment_system_id' => $paymentSystem->id
                ]
            );
            foreach ($orden->items as $product){
                //para cada uno de los productos de la orden...
                //creo un Item o lo actualizo si este ya existiese.
                $item = Item::updateOrCreate(
                    ['item_id'=>$product->productId],
                    [
                        'nombre' => $product->name,
                        'precio_lista' => $product->listPrice
                    ]
                );
                //creo la relacion en la tabla pivot entre el Item y la Orden
                $order->items()->syncWithoutDetaching([$item->id =>
                    ['cantidad' => $product->quantity,
                        'precio' => $product->price
                    ]
                ]);
            }
        }
    }

    public function getOrder($orderId){
        //inicializo el curl
        $curl = curl_init();

        //confirguro los parametros del curl
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://knownonline.vtexcommercestable.com.br/api/oms/pvt/orders/".$orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "X-VTEX-API-AppKey: vtexappkey-knownonline-IPWBFW",
                "X-VTEX-API-AppToken: CVBSREIACFNEEBYQWRZZEGPEJJMYKTFKZUBGQDIAZICUEGRPXZZYKLWVFWJHSKQJZCFJASASIZAVEUACSWAKTGAOYGATUBIPSTVCBHPFZHPLKBRKWGOVJFPSBQLTRGXH"
            ],
        ]);
        //obtengo la respuesta o error del curl
        $response = curl_exec($curl);
        $err = curl_error($curl);

        //cierro el curl
        curl_close($curl);

        if ($err) {
            //si $err = true devuelvo el error
            return $err;
        } else {
            //si no devuelvo la respuesta del curl
            return $response;
        }
    }

    //con esta funcion manejo la paginacion y devuelvo la lista completa de orderId, segun los parametros fijados
    public function getOrdersIdList ($toDate,$fromDate,$status) {
        $page = 0;
        $ordersIdList = [];
        //hago una primera ejecucion de getOrdersListPage y analizo la respuesta
        do {
            $page += 1;
            //llamo a la funcion getOrdersListPage, que me devuelve una lista de orderId
            $orders = $this->getOrdersListPage($fromDate, $toDate, $status, $page);
            $result = json_decode($orders);
            //voy haciendo un merge de la respuesta de getOrdersListPage en el array $ordersIdList
            $ordersIdList = array_merge($ordersIdList, array_column($result->list,'orderId')) ;
            //asigno los valores de paginacion a las variables
            $page = $result->paging->currentPage;
            $pages = $result->paging->pages;
        }
        //analizo si hay una pagina siguiente para llamar
        while($page<$pages);
        //devuelvo una lista con todos los orderId
        return $ordersIdList;
    }

    //con esta funcion devuelvo una lista de orderId, segun los parametros fijados
    public function getOrdersListPage($fromDate, $toDate, $status, $page){
        //codifico los parametros para poder usarlos en el curl
        $fromDate =urlencode($fromDate);
        $toDate =urlencode($toDate);

        //inicializo el curl
        $curl = curl_init();

        //confirguro los parametros del curl
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://knownonline.vtexcommercestable.com.br/api/oms/pvt/orders?f_creationDate=creationDate%3A%5B".$fromDate."%20TO%20".$toDate."%5D&f_hasInputInvoice=false&f_status=".$status."&page=".$page,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
                "X-VTEX-API-AppKey: vtexappkey-knownonline-IPWBFW",
                "X-VTEX-API-AppToken: CVBSREIACFNEEBYQWRZZEGPEJJMYKTFKZUBGQDIAZICUEGRPXZZYKLWVFWJHSKQJZCFJASASIZAVEUACSWAKTGAOYGATUBIPSTVCBHPFZHPLKBRKWGOVJFPSBQLTRGXH"
            ],
        ]);

        //obtengo la respuesta o error del curl
        $response = curl_exec($curl);
        $err = curl_error($curl);

        //cierro el curl
        curl_close($curl);

        if ($err) {
            //si $err = true devuelvo el error
            return $err;
        } else {
            //si no devuelvo la respuesta del curl
            return $response;
        }
    }
}
