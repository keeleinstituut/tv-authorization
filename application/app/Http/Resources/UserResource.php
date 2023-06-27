<?php

namespace App\Http\Resources;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin User
 */
#[OA\Schema(
    title: 'User',
    required: ['id', 'personal_identification_code', 'forename', 'surname', 'created_at', 'updated_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'personal_identification_code', type: 'string'),
        new OA\Property(property: 'forename', type: 'string'),
        new OA\Property(property: 'surname', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
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
