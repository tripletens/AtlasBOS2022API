<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    use HasFactory;

    protected $fillable = [
        'dealer_id',
        'account_id',
        'email',
        'password',
        'location',
        'phone',
        'account_id',
        'status'
    ];
}
