<?php

namespace App\Models;

use App\Enum\InstitutionUserStatusKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionUserStatus extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['key'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, mixed>
     */
    protected $casts = [
        'key' => InstitutionUserStatusKey::class,
    ];

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }
}
