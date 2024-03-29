<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['currency_code','decimal_length','description','exchange_rate','symbol'];
}
