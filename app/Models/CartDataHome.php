<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartDataHome extends Model
{
      protected $connection = 'mysql3'; 
    
    use HasFactory;
    protected $fillable = ['cart_id','category_id', 'product_id', 'product_type', 'order_type', 'quantity','voucher_amount', 'included_products','autoships', 'is_product_group'
    ,'is_membership', 'service_ids', 'custom_field4', 'status', 'is_pack', 'show_badge'];
}
