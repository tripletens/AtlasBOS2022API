<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtlasLoginLog extends Model {
    use HasFactory;

    protected $table = 'atlas_login_log';

    protected $fillable = [
        'dealer',
        'login_time',
        'ip_address',
        'location',
        'browser',
        'current_location',
        'data',
        'browser_data'

    ];

}
