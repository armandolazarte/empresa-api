<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentAcount extends Model
{
    protected $guarded = [];

    public function sale() {
        return $this->belongsTo('App\Models\Sale');
    }

    public function articles() {
        return $this->belongsToMany('App\Models\Article')->withPivot('amount', 'price');
    }

    public function budget() {
        return $this->belongsTo('App\Models\Budget');
    }

    public function order_production() {
        return $this->belongsTo('App\Models\OrderProduction');
    }

    public function provider_order() {
        return $this->belongsTo('App\Models\ProviderOrder');
    }

    public function checks() {
        return $this->hasMany('App\Models\Check');
    }

    public function current_acount_payment_methods() {
        return $this->belongsToMany('App\Models\CurrentAcountPaymentMethod')->withPivot('amount', 'bank', 'num', 'payment_date', 'credit_card_id', 'credit_card_payment_plan_id');
    }

    public function client() {
        return $this->belongsTo('App\Models\Client');
    }

    public function seller() {
    	return $this->belongsTo('App\Models\Seller');
    }
}
