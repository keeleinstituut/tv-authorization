<?php

namespace App\Http\Resources\API;

use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Privilege
 */
class PrivilegeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key->value,
        ];
    }
}
