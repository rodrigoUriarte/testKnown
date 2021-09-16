<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }

    public function paymentSystem()
    {
        return $this->belongsTo('App\Models\PaymentSystem');
    }

    public function items()
    {
        return $this->belongsToMany('App\Models\Item')->withTimestamps();
    }

}
