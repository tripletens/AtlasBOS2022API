<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalogue_Order extends Model
 {
    use HasFactory;

    protected $table = 'atlas_catalogue_orders';

    protected $fillable = [ 'dealer', 'data', 'created_at', 'updated_at', 'deleted_at','completed'];

}
