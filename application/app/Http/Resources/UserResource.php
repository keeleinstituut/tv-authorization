<?php

namespace App\Http\Resources;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     personal_identification_code: string,
     *     forename: string,
     *     surname: string,
     *     updated_at: Carbon,
     *     created_at: Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'personal_identification_code',
            'forename',
            'surname',
            'updated_at',
            'created_at'
        );
    }
}
