<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSystem extends Model
{
    protected $guarded = ['id'];

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }}
