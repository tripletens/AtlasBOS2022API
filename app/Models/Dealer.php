<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Dealer extends Authenticatable implements JWTSubject {
    use HasFactory;

    protected $table = 'atlas_dealers';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'location',
        'phone',
        'account_id',
        'status',
        'password_clear',
        'username',
        'last_login',
        'company_name',
        'full_name',
        'placed_order_date'
    ];

    protected $hidden = [ 'password' ];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

}
