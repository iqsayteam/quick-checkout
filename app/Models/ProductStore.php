<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStore extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['price_id', 'store_id'];
}
