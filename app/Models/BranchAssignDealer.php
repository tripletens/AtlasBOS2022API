<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchAssignDealer extends Model
{
    use HasFactory;

    protected $table = 'atlas_branch_assign_dealers';

    protected $fillable = [
        'branch_id', 'dealer_id', 'status', 'created_at', 'deleted_at', 'updated_at'
    ];

    public function events()
    {
        return $this->hasMany(App\Models\Event::class);
    }
}
