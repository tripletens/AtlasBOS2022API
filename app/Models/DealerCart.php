<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerCart extends Model {
    use HasFactory;

    protected $table = 'atlas_user_cart';

    protected $fillable = [
        'user_id',
        'cart_data',
        'status',
        'order_status',
        'ref'
    ];
}
