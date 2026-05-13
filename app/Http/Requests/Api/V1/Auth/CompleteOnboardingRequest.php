<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CompleteOnboardingRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'emergency_contact' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
