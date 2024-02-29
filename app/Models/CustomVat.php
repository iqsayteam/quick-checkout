<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomVat extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['recordnumber', 'countryCode', 'taxClassID', 'taxRate'];
}
