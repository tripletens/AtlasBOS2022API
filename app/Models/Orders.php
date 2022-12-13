<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;

    protected $table = "atlas_orders";

    protected $fillable = [
        'name', 'email', 'type', 'status','url'
    ];
}
