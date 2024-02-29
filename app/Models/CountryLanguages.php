<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryLanguages extends Model
{
    //we are getting this table fron go.nvisionu.com app
    protected $connection = 'mysql2'; 
    use HasFactory;

    protected $fillable = [
        'country_code',
        'language_code',
        'language_name'
    ];
}
