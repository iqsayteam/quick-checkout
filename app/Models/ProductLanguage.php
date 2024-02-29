<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLanguage extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['product_id', 'description', 'language_code', 'product_name', 'seo_keywords', 'specifications', 'field_1', 'field_2', 'field_3'];
}
