<?php

namespace App\Http\Controllers;

use App\Helpers\UserFullNameParser;
use App\Http\OpenApiHelpers as OAH;
use App\Http\Requests\ImportUsersCsvRequest;
use App\Http\Requests\ImportUsersCsvRowValidationRequest;
use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\Scopes\ExcludeArchivedInstitutionUsersScope;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Models\Scopes\ExcludeIfRelatedUserSoftDeletedScope;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\InstitutionUserPolicy;
use App\Policies\RolePolicy;
use App\Rules\CsvContentValidator;
use DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

class InstitutionUserImportController extends Controller
{
    /**
     * @throws Throwable
     */
    #[OA\Post(
        path: '/institution-users/validate-import-csv',
        summary: 'Check the supplied intitution users CSV for validation errors',
        requestBody: new OAH\RequestBody(ImportUsersCsvRequest::class),
        responses: [new OAH\Forbidden, new OAH\Unauthorized]
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'The supplied CSV contents had no validation errors',
        content: new OA\JsonContent(
            required: ['errors', 'rowsWithExistingInstitutionUsers'],
            properties: [
                new OA\Property(property: 'errors', type: 'array', items: new OA\Items, maxItems: 0),
                new OA\Property(
                    property: 'rowsWithExistingInstitutionUsers',
                    type: 'array',
                    items: new OA\Items(type: 'integer')
                ),
            ],
            type: 'object',
        )
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation errors found in supplied CSV contents',
        content: new OA\JsonContent(
            required: ['errors', 'rowsWithExistingInstitutionUsers'],
            properties: [
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        required: ['row', 'errors'],
                        properties: [
                            new OA\Property(property: 'row', type: 'integer'),
                            new OA\Property(property: 'errors', type: 'object', example: '{"role":["The role with the name \'role-name\' does not exist."], "email":["The email field must be a valid email address."]}'),
                        ],
                        type: 'object'
                    )
                ),
                new OA\Property(
                    property: 'rowsWithExistingInstitutionUsers',
                    type: 'array',
                    items: new OA\Items(type: 'integer')
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Unable to check for validation errors because the supplied CSV was in an incorrect format'
    )]
    public function validateCsv(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);
        $validator = $this->getCsvContentValidator($request->file('file'));
        try {
            $rowsWithErrors = [];
            $rowsWithExistingInstitutionUsers = [];
            foreach ($validator->validatedRows() as $idx => $attributes) {
                if (filled($attributes['errors'])) {
                    $rowsWithErrors[] = [
                        'row' => $idx,
                        'errors' => $attributes['errors'],
                    ];
                }

                $this->isExistingInstitutionUser(
                    $attributes['personal_identification_code']
                ) && $rowsWithExistingInstitutionUsers[] = $idx;
            }
        } catch (UnexpectedValueException) {
            return response()->json(
                ['message' => 'The file has incorrect format'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'errors' => $rowsWithErrors,
            'rowsWithExistingInstitutionUsers' => $rowsWithExistingInstitutionUsers,
        ], filled($rowsWithErrors) ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    /**
     * @throws AuthorizationException
     * @throws Throwable
     */
    #[OA\Post(
        path: '/institution-users/import-csv',
        summary: 'Import institution users into the database from the supplied CSV file',
        requestBody: new OAH\RequestBody(ImportUsersCsvRequest::class),
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OA\Response(response: Response::HTTP_OK, description: 'Import was successful')]
    #[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Import was unsuccessful; CSV had unresolved errors')]
    public function importCsv(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);
        $institutionId = Auth::user()?->institutionId;
        $validator = $this->getCsvContentValidator($request->file('file'));

        return DB::transaction(function () use ($validator, $institutionId): JsonResponse {
            $roles = Role::query()->withGlobalScope('policy', RolePolicy::scope())
                ->pluck('id', 'name');
            $departments = Department::query()->withGlobalScope('policy', DepartmentPolicy::scope())
                ->pluck('id', 'name');
            try {
                foreach ($validator->validatedRows() as $attributes) {
                    if (filled($attributes['errors'])) {
                        throw new UnexpectedValueException('File contains unresolved errors');
                    }

                    if ($this->isExistingInstitutionUser($attributes['personal_identification_code'])) {
                        continue;
                    }

                    [$forename, $surname] = UserFullNameParser::parse(trim($attributes['name']));
                    $user = User::withTrashed()->firstOrCreate([
                        'personal_identification_code' => $attributes['personal_identification_code'],
                    ], [
                        'forename' => $forename,
                        'surname' => $surname,
                    ]);

                    $institutionUser = InstitutionUser::make([
                        'user_id' => $user->id,
                        'institution_id' => $institutionId,
                        'email' => $attributes['email'],
                        'phone' => $attributes['phone'],
                    ]);
                    $institutionUser->saveOrFail();

                    $roleIds = collect(explode(',', $attributes['role']))
                        ->map(fn (string $roleName) => $roles->get(trim($roleName)));
                    $institutionUser->roles()->sync($roleIds);

                    if (filled($attributes['department'])) {
                        $institutionUser->department()->associate($departments->get($attributes['department']));
                        $institutionUser->saveOrFail();
                    }

                    $this->auditLogPublisher->publishCreateObject($institutionUser);
                }
            } catch (UnexpectedValueException) {
                return response()->json([
                    'message' => 'The file contains unresolved errors',
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json(status: Response::HTTP_OK);
        });
    }

    /**
     * @throws AuthorizationException
     */
    #[OA\Post(
        path: '/institution-users/validate-import-csv-row',
        summary: 'Check the supplied data, representing a single CSV row, for validation errors',
        requestBody: new OAH\RequestBody(ImportUsersCsvRowValidationRequest::class),
        responses: [new OAH\Forbidden, new OAH\Unauthorized, new OAH\Invalid]
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Validation information about the supplied data row',
        content: new OA\JsonContent(
            required: ['data', 'isExistingInstitutionUser'],
            properties: [
                new OA\Property(
                    property: 'data',
                    description: 'Only the subset of sent data which passed validation',
                    properties: [
                        new OA\Property(property: 'personal_identification_code', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'phone', type: 'string', format: 'phone'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'department', type: 'string', nullable: true),
                        new OA\Property(property: 'role', type: 'string', example: 'Tõlk,Tõlkekorraldaja,Peakasutaja'),
                    ],
                    type: 'object'
                ),
                new OA\Property(property: 'isExistingInstitutionUser', type: 'boolean'),
            ],
            type: 'object',
        )
    )]
    public function validateCsvRow(ImportUsersCsvRowValidationRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        return response()->json([
            'data' => $request->validated(),
            'isExistingInstitutionUser' => $this->isExistingInstitutionUser(
                $request->validated('personal_identification_code')
            ),
        ], Response::HTTP_OK);
    }

    private function getCsvContentValidator(UploadedFile $file): CsvContentValidator
    {
        return (new CsvContentValidator($file->getPathname()))
            ->setExpectedHeaders([
                'Isikukood', 'Nimi', 'Meiliaadress', 'Telefoninumber', 'Üksus', 'Roll',
            ])->setAttributesNames([
                'personal_identification_code', 'name', 'email', 'phone', 'department', 'role',
            ])->setRules((new ImportUsersCsvRowValidationRequest)->rules());
    }

    private function isExistingInstitutionUser(string $pin): bool
    {
        return InstitutionUser::withTrashed()->whereRelation('user',
            fn (Builder $query) => $query->withTrashed()->where('personal_identification_code', $pin)
        )->withGlobalScope('policy', InstitutionUserPolicy::scope())
            ->withoutGlobalScope(ExcludeArchivedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeDeactivatedInstitutionUsersScope::class)
            ->withoutGlobalScope(ExcludeIfRelatedUserSoftDeletedScope::class)
            ->exists();
    }
}
