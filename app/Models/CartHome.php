<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartHome extends Model
{
     protected $connection = 'mysql3'; 
    
    use HasFactory;

    protected $fillable = ['user_id', 'language','country_code'];

    public function cartdata()
    {
        return $this->hasMany(CartData::class, 'cart_id');
    }

    public function cartautoship(){
        return $this->hasMany(CartData::class, 'cart_id')->where('order_type', 2);
    }
}
