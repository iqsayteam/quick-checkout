<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionData extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['option_id','option','sku_ext'];
}
