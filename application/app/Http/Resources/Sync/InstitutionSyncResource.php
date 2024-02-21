<?php

namespace App\Http\Resources\Sync;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Institution
 */
class InstitutionSyncResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     phone: string,
     *     email: string,
     *     short_name: string,
     *     logo_url: string,
     *     deleted_at: Carbon,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->only(
                'id',
                'name',
                'short_name',
                'phone',
                'email',
                'logo_url',
                'deleted_at'
            ),
            'vacations' => $this->whenLoaded('vacations'),
        ];
    }
}
