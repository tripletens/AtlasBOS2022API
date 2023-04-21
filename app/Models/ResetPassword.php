<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    use HasFactory;

    protected $table = "reset_dealer_passwords";
    protected $fillable = [
        'dealer_id',
        'email',
        'code',
        'status',
        'account_id'
    ];
}
