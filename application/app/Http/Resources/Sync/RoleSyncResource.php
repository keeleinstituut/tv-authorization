<?php

namespace App\Http\Resources\Sync;

use App\Http\Resources\API\PrivilegeResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleSyncResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'name',
                'institution_id'
            ),
            'privileges' => PrivilegeResource::collection($this->privileges),
        ];
    }
}
