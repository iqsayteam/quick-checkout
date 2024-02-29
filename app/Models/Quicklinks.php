<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class Quicklinks extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'email', 
        'customer_id',  
        'items',  
        'quicklink',  
        'status',  
    ];

}
