<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mysql2'; 
    use HasFactory;
    protected $fillable = ['product_id', 'product_sku','disabled', 'category_id', 'default_image', 'stock','slug', 'display_order', 'group_id', 'hide_from_product_list','hide_view','status','TaxClassId'];

    public function languages()
    {
        return $this->hasmany(ProductLanguage::class, 'product_id', 'product_id');
    }
    public function product_price()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'product_id');
    }
    public function custom_fields()
    {
        return $this->hasmany(CustomField::class, 'product_id', 'product_id');
    }
}
