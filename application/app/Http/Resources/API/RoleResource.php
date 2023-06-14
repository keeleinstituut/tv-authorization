<?php

namespace App\Http\Resources\API;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
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
                'institution_id',
                'created_at',
                'updated_at',
                'is_root',
            ),
            'privileges' => PrivilegeResource::collection($this->privileges),
        ];
    }
}
