<?php

namespace App\Http\Resources\API;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Role
 */
#[OA\Schema(
    title: 'Role',
    required: ['id', 'institution_id', 'name', 'created_at', 'updated_at', 'is_root', 'privileges'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'institution_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'is_root', type: 'boolean', default: false),
        new OA\Property(property: 'privileges', type: 'array', items: new OA\Items(ref: PrivilegeResource::class)),
    ],
    type: 'object'
)]
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
