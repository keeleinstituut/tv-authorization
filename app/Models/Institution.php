<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
