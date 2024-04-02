<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class serviceProduct extends Model
{
    use HasApiTokens, HasFactory, Notifiable; 
    protected $table = 'serivceproduct_for_expiry_notification';
    protected $fillable = [
        'user_id', 
        'item_ids',
        'service_id',
        'created_at',
        'updated_at'
    ];
}
