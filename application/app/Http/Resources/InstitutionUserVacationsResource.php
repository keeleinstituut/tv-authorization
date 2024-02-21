<?php

namespace App\Http\Resources;

use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InstitutionUser
 */
class InstitutionUserVacationsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'institution_user_vacations' => InstitutionUserVacationResource::collection(
                $this->whenLoaded('activeInstitutionUserVacations')
            ),
            'institution_vacations' => InstitutionVacationResource::collection(
                $this->getActiveInstitutionVacationsWithExclusions()
            )
        ];
    }
}
