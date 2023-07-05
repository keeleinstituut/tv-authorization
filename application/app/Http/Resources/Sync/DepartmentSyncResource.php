<?php

namespace App\Http\Resources\Sync;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Department
 */
class DepartmentSyncResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     institution_id: string,
     *     name: string
     * }
     */
    public function toArray(Request $request): array
    {
        return $this->only(
            'id',
            'institution_id',
            'name',
        );
    }
}
