<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionUserStatus;
use App\Http\Requests\ImportUsersCsvRequest;
use App\Http\Requests\ImportUsersCsvRowValidationRequest;
use App\Models\InstitutionUser;
use App\Models\InstitutionUserRole;
use App\Models\Role;
use App\Models\User;
use App\Policies\Scopes\RoleScope;
use App\Rules\CsvContentValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

class InstitutionUserImportController extends Controller
{
    /**
     * @throws Throwable
     */
    public function validateFile(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        $validator = $this->getFileValidator($request->file('file'));
        try {
            $rowsWithErrors = [];
            foreach ($validator->validatedRows() as $idx => $attributes) {
                if (! empty($attributes['errors'])) {
                    $rowsWithErrors[] = [
                        'row' => $idx,
                        'errors' => $attributes['errors'],
                    ];
                }
            }
        } catch (UnexpectedValueException) {
            return response()->json(
                ['message' => 'The file has incorrect format'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (! empty($rowsWithErrors)) {
            return response()->json([
                'errors' => $rowsWithErrors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'errors' => [],
        ], Response::HTTP_OK);
    }

    /**
     * @throws AuthorizationException
     */
    public function import(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        /** @var JwtPayloadUser $activeUser */
        $activeUser = Auth::user();
        $validator = $this->getFileValidator($request->file('file'));
        try {
            $rolesMap = Role::query()->withGlobalScope('auth', new RoleScope)
                ->pluck('id', 'name')
                ->toArray();

            foreach ($validator->validatedRows() as $attributes) {
                if (! empty($attributes['errors'])) {
                    throw new UnexpectedValueException('File contains errors');
                }

                $nameParts = explode(' ', $attributes['name']);
                $user = User::firstOrCreate([
                    'personal_identification_code' => $attributes['personal_identification_code'],
                ], [
                    'forename' => $nameParts[0],
                    'surname' => $nameParts[1] ?? '',
                ]);

                $institutionUser = InstitutionUser::firstOrCreate([
                    'user_id' => $user->id,
                    'institution_id' => $activeUser->institutionId,
                ], [
                    'email' => $attributes['email'],
                    'phone' => $attributes['phone'],
                    'status' => InstitutionUserStatus::Created,
                ]);

                if (! $institutionUser->wasRecentlyCreated) {
                    continue;
                }

                $roleNames = explode(',', $attributes['role']);
                foreach ($roleNames as $roleName) {
                    $roleId = $rolesMap[trim($roleName)] ?? null;

                    if (empty($roleId)) {
                        throw new RuntimeException('Role not found');
                    }

                    InstitutionUserRole::firstOrCreate([
                        'institution_user_id' => $institutionUser->id,
                        'role_id' => $roleId,
                    ]);
                }

                // TODO: add vendor initialization
                // TODO: add department tag initialization
                // TODO: add audit logs
            }
        } catch (UnexpectedValueException) {
            return response()->json(
                ['message' => 'The file contains unresolved errors'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'data' => [],
        ], Response::HTTP_OK);
    }

    /**
     * @throws AuthorizationException
     */
    public function validateRow(ImportUsersCsvRowValidationRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        return response()->json([
            'data' => $request->validated(),
        ], Response::HTTP_OK);
    }

    private function getFileValidator(UploadedFile $file): CsvContentValidator
    {
        return (new CsvContentValidator($file->getPathname()))
            ->setExpectedHeaders([
                'sikukood', 'nimi', 'meiliaadress', 'telefoninumber', 'üksus', 'roll', 'teostaja',
            ])->setAttributesNames([
                'personal_identification_code', 'name', 'email', 'phone', 'department', 'role', 'is_vendor',
            ])->setRules((new ImportUsersCsvRowValidationRequest)->rules());
    }
}
