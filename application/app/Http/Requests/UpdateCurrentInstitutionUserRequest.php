<?php

namespace App\Http\Requests;

use App\Enums\InstitutionUserStatus;
use App\Models\Department;
use App\Models\InstitutionUser;
use App\Models\Role;
use App\Models\Scopes\ExcludeDeactivatedInstitutionUsersScope;
use App\Rules\ModelBelongsToInstitutionRule;
use App\Rules\PhoneNumberRule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property string forename
 * @property string surname
 * @property string email
 * @property string phone
 * @property array<string> roles
 * @property string department
 * @property string vendor
 */
#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(
                property: 'user',
                minProperties: 1,
                properties: [
                    new OA\Property(property: 'forename', type: 'string'),
                    new OA\Property(property: 'surname', type: 'string'),
                ],
                type: 'object'
            ),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'phone', type: 'string', format: 'phone')
        ]
    )
)]
class UpdateCurrentInstitutionUserRequest extends FormRequest
{
    private ?InstitutionUser $targetInstitutionUser = null;

    public function rules(): array
    {
        return [
            'user' => ['array', 'min:1'],
            'user.forename' => 'filled',
            'user.surname' => 'filled',
            'email' => 'email',
            'phone' => new PhoneNumberRule,
        ];
    }
}
