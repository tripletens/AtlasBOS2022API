<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model {
    use HasFactory;

    protected $table = 'cart';

    protected $fillable = [
        'dealer', 'pro_id', 'atlas_id', 'qty', 'price', 'unit_price', 'status', 'desc', 'pro_img', 'vendor_img', 'spec_data', 'grouping', 'booking', 'category', 'um', 'xref'
    ];
}
