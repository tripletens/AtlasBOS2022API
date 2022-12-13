<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;

    use SoftDeletes;
    
    protected $table = 'atlas_categories';

    protected $fillable = [
        'name', 'description','image','color_code','created_at', 'updated_at', 'deleted_at', 'status'
    ];
}
