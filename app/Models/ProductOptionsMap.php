<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionsMap extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['product_id','key','checked','item_id','image','ext_sku','stock_levels'];
}
