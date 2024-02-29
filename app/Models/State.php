<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $table = 'states';
    protected $fillable = [
        'country_code',
        'state_code',
        'state_name',
        'created_at',
        'updated_at'
    ];

}
