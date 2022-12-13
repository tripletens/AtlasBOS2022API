<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class SalesRep extends Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $table = 'atlas_sales_reps';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'location',
        'phone',
        'account_id',
        'status'
    ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }
}
