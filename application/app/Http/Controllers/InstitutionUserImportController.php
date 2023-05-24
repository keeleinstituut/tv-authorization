<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUsersCsvRequest;
use App\Http\Requests\ImportUsersCsvRowValidationRequest;
use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\User;
use App\Policies\DepartmentPolicy;
use App\Policies\RolePolicy;
use App\Rules\CsvContentValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

class InstitutionUserImportController extends Controller
{
    /**
     * @throws Throwable
     */
    public function validateCsv(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        $validator = $this->getCsvContentValidator($request->file('file'));
        try {
            $rowsWithErrors = [];
            foreach ($validator->validatedRows() as $idx => $attributes) {
                if (filled($attributes['errors'])) {
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
    public function importCsv(ImportUsersCsvRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        /** @var JwtPayloadUser $activeUser */
        $activeUser = Auth::user();
        $validator = $this->getCsvContentValidator($request->file('file'));
        try {
            $roles = Role::query()->withGlobalScope('policy', RolePolicy::scope())
                ->pluck('id', 'name');

            $departments = Department::query()->withGlobalScope('policy', DepartmentPolicy::scope())
                ->pluck('id', 'name');

            $warnings = [];
            foreach ($validator->validatedRows() as $attributes) {
                if (filled($attributes['errors'])) {
                    throw new UnexpectedValueException('File contains errors');
                }

                $nameParts = explode(' ', $attributes['name']);
                $user = User::firstOrCreate([
                    'personal_identification_code' => $attributes['personal_identification_code'],
                ], [
                    'forename' => $nameParts[0],
                    'surname' => $nameParts[1],
                ]);

                $institutionUser = InstitutionUser::firstOrCreate([
                    'user_id' => $user->id,
                    'institution_id' => $activeUser->institutionId,
                ], [
                    'email' => $attributes['email'],
                    'phone' => $attributes['phone'],
                ]);

                if (! $institutionUser->wasRecentlyCreated) {
                    continue;
                }

                $roleIds = array_map(function (string $roleName) use ($roles) {
                    return $roles->get(trim($roleName));
                }, explode(',', $attributes['role']));

                $institutionUser->roles()->sync($roleIds);

                if (filled($attributes['department'])) {
                    $institutionUser->department()->associate($departments->get($attributes['department']));
                }

                $institutionUser->save();

                // TODO: add audit logs
            }
        } catch (UnexpectedValueException) {
            return response()->json(
                ['message' => 'The file contains unresolved errors'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'data' => [
                'warnings' => $warnings,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * @throws AuthorizationException
     */
    public function validateCsvRow(ImportUsersCsvRowValidationRequest $request): JsonResponse
    {
        $this->authorize('import', InstitutionUser::class);

        return response()->json([
            'data' => $request->validated(),
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
}
