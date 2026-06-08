<?php

namespace App\Http\Controllers\API;

use App\Enums\PrivilegeKey;
use App\Http\Controllers\Controller;
use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\API\PrivilegeListRequest;
use App\Http\Resources\API\PrivilegeResource;
use App\Models\AuthUser;
use App\Models\Privilege;
use App\Policies\PrivilegePolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PrivilegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/privileges',
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OAH\CollectionResponse(itemsRef: PrivilegeResource::class, description: 'All privileges used in Tõlkevärav')]
    public function index(PrivilegeListRequest $request): AnonymousResourceCollection
    {
        try {
            $this->authorize('viewAny', Privilege::class);
        } catch (AuthorizationException) {
            return PrivilegeResource::collection([]);
        }
        /** @var AuthUser|null $user */
        $user = $request->user();

        $query = $this->getBaseQuery()
            ->orderBy('key');

        if ($user?->belongsToTranslationAgency()) {
            $query->whereIn('key', PrivilegeKey::TRANSLATION_AGENCY_ALLOWED_PRIVILEGES);
        }

        return PrivilegeResource::collection($query->get());
    }

    public function getBaseQuery()
    {
        return Privilege::getModel()->withGlobalScope('policy', PrivilegePolicy::scope());
    }
}
