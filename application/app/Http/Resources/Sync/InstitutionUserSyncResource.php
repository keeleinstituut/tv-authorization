<?php

namespace App\Http\Resources\Sync;

use App\Http\Resources\InstitutionUserResource;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserSyncResource extends InstitutionUserResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'deleted_at' => $this->deleted_at?->toISOString()
        ];
    }
}
