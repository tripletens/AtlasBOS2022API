<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotional_ads extends Model
{
    use HasFactory;

    protected $table = "atlas_promotional_ads";
    protected $fillable = ['category_id', 'name', 'pdf_url', 'description', 'image_url','status', 'created_at', 'updated_at', 'deleted_at'];
}
