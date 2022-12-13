<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceParts extends Model {
    use HasFactory;

    protected $table = 'atlas_service_parts';

    protected $fillable = [
        'dealer',
        'atlas_id',
        'data',
        'completed'
    ];
}
