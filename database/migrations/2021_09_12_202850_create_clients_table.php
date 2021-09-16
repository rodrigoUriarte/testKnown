<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->unique();
            //se agrega APELLIDO y NOMBRE como nullable, porque la API con rangos de fechas mas amplios
            //devuelve registros con este atributo vacio, la otra opcion es hacer un nvl al insertar.
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('email');
            $table->timestamps();
        });
    }
    //1132821962541-01
        //"uniqueId":"D07F0F8C8AA143F99562AF312C58591D"
        //"id":"141"
        //"productId":"170"
        //"name":"PRUEBA POLERA JT sku-interno-polera"
    //1131670551486-01
        //"uniqueId":"2D7DA526B17E48C48044B4A8A40E7F95"
        //"id":"141"
        //"productId":"170"
        //"name":"PRUEBA POLERA JT sku-interno-polera"

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
