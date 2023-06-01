<?php

namespace App\Http\Resources;

use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Department
 */
class DepartmentResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     institution_id: string,
     *     name: string,
     *     updated_at: Carbon,
     *     created_at: Carbon
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'institution_id',
            'name',
            'created_at',
            'updated_at'
        );
    }
}
