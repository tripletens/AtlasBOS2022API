<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
 {
    use HasFactory;
    protected $table = 'atlas_products';

    protected $fillable = [
        'short_note','short_note_url','atlas_id', 'name', 'price', 'description', 'img', 'assorted_discount', 'quantity_discount', 'status', 'vendor_logo', 'xref', 'um', 'booking', 'special', 'cond', 'type', 'grouping', 'vendor_name', 'full_desc', 'spec_data', 'category', 'short_note'
    ];
}
