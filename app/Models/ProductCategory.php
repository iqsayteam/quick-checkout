<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['category_id', 'name', 'description','display_order', 'image_url', 'parent_id', 'list_products', 'list_products_in_cms', 'list_products_in_backoffice', 'has_children', 'sub_categories'];

}
