<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Branch extends Authenticatable implements JWTSubject 
{
    use HasFactory;

    protected $table = 'atlas_branches';

    protected $fillable = [
        'email', 'password', 'password_clear', 'name', 'first_name', 'last_name', 'username', 'phone', 'location', 'status', 'updated_at', 'created_at', 'deleted_at'
    ];

    protected $hidden = ['password'];

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }
}


