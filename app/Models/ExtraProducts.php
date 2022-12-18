<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraProducts extends Model
{
    use HasFactory;

    protected $table = 'extra_products';

    protected $fillable = [
        'item_code',
        'vendor_code',
        'description',
        'type',
        'type_name',
    ];
}
