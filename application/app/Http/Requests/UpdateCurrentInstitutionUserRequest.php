<?php

namespace App\Http\Requests;

use App\Http\Requests\Helpers\MaxLengthValue;
use App\Models\InstitutionUser;
use App\Rules\PhoneNumberRule;
use App\Rules\UserFullNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use OpenApi\Attributes as OA;

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
            new OA\Property(property: 'phone', type: 'string', format: 'phone'),
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
            'user.forename' => ['filled', 'max:'.MaxLengthValue::USERNAME_PART],
            'user.surname' => ['filled', 'max:'.MaxLengthValue::USERNAME_PART],
            'email' => 'email',
            'phone' => new PhoneNumberRule,
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $userFullName = join(' ', [
                    $this->validated('user.forename'),
                    $this->validated('user.surname'),
                ]);

                (new UserFullNameRule())->validate(
                    'user.forename',
                    $userFullName,
                    fn (string $message) => $validator->errors()->add('user.forename', $message)
                );
            }
        ];
    }
}
