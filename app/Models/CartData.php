<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartData extends Model
{
    protected $connection = 'mysql2'; 
    
    use HasFactory;
    protected $fillable = ['cart_id', 'user_id', 'product_id', 'order_type','quantity', 'custom_fields','custom_field1', 'custom_field2'
    ,'custom_field3', 'custom_field4', 'custom_field5','category_id','is_membership', 'is_popup_show','add_on_product','subscription_of', 'section_name'];
}
