<?php

namespace App\Http\Resources;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Institution
 */
class InstitutionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     logo_url: string,
     *     updated_at: Carbon,
     *     created_at: Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'name',
            'logo_url',
            'created_at',
            'updated_at',
        );
    }
}
