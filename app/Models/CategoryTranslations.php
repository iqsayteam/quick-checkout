<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTranslations extends Model
{
    use HasFactory;
	 protected $connection = 'mysql2'; 
	 public $timestamps = false;
    protected $table = 'category_translations';
    protected $fillable = [
        'category_id',
        'language',
        'translation_value',
    ]; 
}
