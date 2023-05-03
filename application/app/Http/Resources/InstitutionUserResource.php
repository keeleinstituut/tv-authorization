<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->user),
            'status' => $this->status,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => RoleMetaResource::collection($this->institutionUserRoles->pluck('role')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
