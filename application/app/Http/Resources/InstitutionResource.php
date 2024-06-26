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
    required: [
        'id',
        'name',
        'phone',
        'email',
        'short_name',
        'logo_url',
        'updated_at',
        'created_at',
        'worktime_timezone',
        'monday_worktime_start',
        'monday_worktime_end',
        'tuesday_worktime_start',
        'tuesday_worktime_end',
        'wednesday_worktime_start',
        'wednesday_worktime_end',
        'thursday_worktime_start',
        'thursday_worktime_end',
        'friday_worktime_start',
        'friday_worktime_end',
        'saturday_worktime_start',
        'saturday_worktime_end',
        'sunday_worktime_start',
        'sunday_worktime_end',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'phone', type: 'string', format: 'phone', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'short_name', type: 'string', nullable: true),
        new OA\Property(property: 'logo_url', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'worktime_timezone', description: 'IANA Timezone Name', type: 'string', example: 'Europe/Tallinn', nullable: true),
        new OA\Property(property: 'monday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'monday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'tuesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'tuesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'wednesday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'wednesday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'thursday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'thursday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'friday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'friday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'saturday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'saturday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
        new OA\Property(property: 'sunday_worktime_start', type: 'string', format: 'time', example: '08:00:00', nullable: true),
        new OA\Property(property: 'sunday_worktime_end', type: 'string', format: 'time', example: '16:00:00', nullable: true),
    ],
    type: 'object'
)]
class InstitutionResource extends JsonResource
{
    public const PUBLIC_REPRESENTATION = 'PUBLIC';
    public const PRIVATE_REPRESENTATION = 'PRIVATE';

    private $representationType = self::PRIVATE_REPRESENTATION;

    public function publicRepresentation(): static
    {
        $this->representationType = self::PUBLIC_REPRESENTATION;
        return $this;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     phone: string|null,
     *     email: string|null,
     *     short_name: string|null,
     *     logo_url: string|null,
     *     updated_at: Carbon,
     *     created_at: Carbon,
     *     worktime_timezone: string|null,
     *     monday_worktime_start: string|null,
     *     monday_worktime_end: string|null,
     *     tuesday_worktime_start: string|null,
     *     tuesday_worktime_end: string|null,
     *     wednesday_worktime_start: string|null,
     *     wednesday_worktime_end: string|null,
     *     thursday_worktime_start: string|null,
     *     thursday_worktime_end: string|null,
     *     friday_worktime_start: string|null,
     *     friday_worktime_end: string|null,
     *     saturday_worktime_start: string|null,
     *     saturday_worktime_end: string|null,
     *     sunday_worktime_start: string|null,
     *     sunday_worktime_end: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return match ($this->representationType) {
            self::PUBLIC_REPRESENTATION => $this->toPublicArray($request),
            self::PRIVATE_REPRESENTATION => $this->toPrivateArray($request),
            default => [],
        };
    }

    private function toPublicArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    private function toPrivateArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_url' => route('institutions.logo', ['institution_id' => $this->id]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'worktime_timezone' => $this->worktime_timezone,

            'monday_worktime_start' => $this->monday_worktime_start,
            'monday_worktime_end' => $this->monday_worktime_end,
            'tuesday_worktime_start' => $this->tuesday_worktime_start,
            'tuesday_worktime_end' => $this->tuesday_worktime_end,
            'wednesday_worktime_start' => $this->wednesday_worktime_start,
            'wednesday_worktime_end' => $this->wednesday_worktime_end,
            'thursday_worktime_start' => $this->thursday_worktime_start,
            'thursday_worktime_end' => $this->thursday_worktime_end,
            'friday_worktime_start' => $this->friday_worktime_start,
            'friday_worktime_end' => $this->friday_worktime_end,
            'saturday_worktime_start' => $this->saturday_worktime_start,
            'saturday_worktime_end' => $this->saturday_worktime_end,
            'sunday_worktime_start' => $this->sunday_worktime_start,
            'sunday_worktime_end' => $this->sunday_worktime_end,
        ];
    }
}
