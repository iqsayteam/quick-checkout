<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
     protected $connection = 'mysql2'; 
    use HasFactory;
	protected $table = 'custom_fields';
    protected $fillable = [
        'product_id',
        'item_id',
        'field1',
        'field2',
        'field3',
        'field4',
        'field5',
        'field6'
    ];

}
