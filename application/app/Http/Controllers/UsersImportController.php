<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportUsersRequest;
use App\Models\Role;
use App\Models\UserToImport;
use App\Policies\Scopes\RoleScope;
use App\Rules\CsvContentValidator;
use App\Rules\PersonalIdCodeRule;
use App\Rules\PhoneNumberRule;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use KeycloakAuthGuard\Models\JwtPayloadUser;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;

class UsersImportController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(ImportUsersRequest $request): JsonResponse
    {
        $this->authorize('manage', UserToImport::class);

        /** @var JwtPayloadUser $user */
        $user = Auth::user();
        $file = $request->file('file');

        $validator = (new CsvContentValidator($file->getPathname()))
            ->setExpectedHeaders([
                'sikukood', 'nimi', 'meiliaadress', 'telefoninumber', 'üksus', 'roll', 'teostaja',
            ])->setAttributesNames([
                'personal_identification_code', 'name', 'email', 'phone', 'department', 'role', 'is_vendor',
            ])->setRules($this->getCsvRowValidationRules());

        try {
            DB::transaction(function () use ($validator, $user) {
                foreach ($validator->validatedRows() as $idx => $attributes) {
                    $attributes['file_row_idx'] = $idx;
                    $attributes['institution_user_id'] = $user->institutionUserId;
                    $attributes['errors_count'] = count($attributes['errors']);
                    $attributes['is_vendor'] = false; // TODO: implement after setup of vendor database.
                    $attributes['department'] = ''; // TODO: implement after setup of tags database.

                    $user = new UserToImport();
                    $user->fill($attributes);
                    $user->saveOrFail();
                }
            });
        } catch (UnexpectedValueException) {
            return response()->json(
                ['message' => 'The file has incorrect format'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return response()->json(['message' => 'The file is imported successfully']);
    }

    private function getCsvRowValidationRules(): array
    {
        return [
            'personal_identification_code' => ['required', new PersonalIdCodeRule],
            'name' => 'required|regex:/^[a-zõäöüšž\- ]+$/iu',
            'email' => 'required|email',
            'phone' => ['required', new PhoneNumberRule],
            'department' => 'nullable|string',
            'role' => [
                'required', 'string',
                function ($attribute, $value, $fail) {
                    $names = explode(',', $value);
                    foreach ($names as $name) {
                        $name = trim($name);
                        if (empty($name)) {
                            continue;
                        }

                        $exists = Role::query()->withGlobalScope('auth', new RoleScope)
                            ->where('name', $name)
                            ->exists();

                        if (! $exists) {
                            $fail("The role with the name '$name' does not exist.");
                        }
                    }
                },
            ],
            'is_vendor' => 'nullable|string',
        ];
    }
}
