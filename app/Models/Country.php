<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Country extends Model
{
    protected $connection = 'mysql2'; 
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'country_code', 
        'currency_code',  
        'country_name',  
        'shop',  
        'enroll',  
    ];
}
