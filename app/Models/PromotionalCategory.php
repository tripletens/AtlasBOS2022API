<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionalCategory extends Model
{
    use HasFactory;
    protected $table = "atlas_promo_category";
    protected $fillable = [ 'name', 'slug', 'description', 'created_at', 'deleted_at', 'status', 'updated_at'];
}
