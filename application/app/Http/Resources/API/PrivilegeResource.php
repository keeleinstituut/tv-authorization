<?php

namespace App\Http\Resources\API;

use App\Enums\PrivilegeKey;
use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Privilege
 */
#[OA\Schema(
    title: 'Privilege',
    required: ['key'],
    properties: [
        new OA\Property(property: 'key', type: 'string', enum: PrivilegeKey::class),
    ],
    type: 'object'
)]

class PrivilegeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key->value,
        ];
    }
}
