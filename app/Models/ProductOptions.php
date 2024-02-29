<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptions extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['product_id','option_id','option','option_type'];
}
