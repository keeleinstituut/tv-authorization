<?php

namespace App\Http\Resources\Sync;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserSyncResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     personal_identification_code: string,
     *     forename: string,
     *     surname: string
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'personal_identification_code',
            'forename',
            'surname'
        );
    }
}
