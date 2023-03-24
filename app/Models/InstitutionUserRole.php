<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionUserRole extends Model
{
    use HasFactory, SoftDeletes;

    public function institutionUser(): BelongsTo
    {
        return $this->belongsTo(InstitutionUser::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
