<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardedProducts extends Model {
    use HasFactory;

    protected $table = 'atlas_carded_products';

    protected $fillable = [
        'dealer',
        'atlas_id',
        'data',
        'completed',
        'deleted_at',
        'updated_at',
        'created_at'
    ];

}
