<?php

namespace App\Http\Resources;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Institution
 */
#[OA\Schema(
    title: 'Institution',
    required: ['id', 'name', 'phone', 'email', 'short_name', 'logo_url', 'updated_at', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'phone', type: 'string', format: 'phone', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'short_name', type: 'string', nullable: true),
        new OA\Property(property: 'logo_url', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    type: 'object'
)]
class InstitutionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     phone: string|null,
     *     email: string|null,
     *     short_name: string|null,
     *     logo_url: string|null,
     *     updated_at: Carbon,
     *     created_at: Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'name',
            'short_name',
            'phone',
            'email',
            'logo_url',
            'created_at',
            'updated_at',
        );
    }
}
