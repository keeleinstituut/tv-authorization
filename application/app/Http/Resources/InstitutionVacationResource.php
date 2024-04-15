<?php

namespace App\Http\Resources;

use App\Models\InstitutionVacation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InstitutionVacation
 */
class InstitutionVacationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'institution_id',
            'start_date',
            'end_date',
            'created_at',
            'updated_at',
        );
    }
}
