<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetInstitutionUserRequest extends FormRequest
{
    public function getInstitutionUserId(): string
    {
        return $this->route('institutionUserId');
    }
}
