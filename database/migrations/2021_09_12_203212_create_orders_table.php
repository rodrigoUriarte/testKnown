<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->decimal('precio_total', 16, 2);
            $table->boolean('procesada');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('payment_system_id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('payment_system_id')->references('id')->on('payment_systems');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
