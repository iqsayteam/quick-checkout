<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['product_id', 'price_id', 'price', 'price_currency', 'cv', 'qv',
     'reward_points_earned', 'start', 'end', 'type', 'stores', 'regions', 'order_type', 'price_groups', 'custom_field','start_date','end_date'];
}
