<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: self::class,
    required: true,
    content: new OA\MediaType(
        mediaType: 'text/csv',
        schema: new OA\Schema(type: 'string'),
        example: "Isikukood;Nimi;Meiliaadress;Telefoninumber;Üksus;Roll\n39511267470;user name;some@email.com;+372 56789566;Lennaberg;Auxiliary Equipment Operator, Sheriff"
    )
)]
class ImportUsersCsvRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['text/plain', 'text/csv'])],
        ];
    }
}
