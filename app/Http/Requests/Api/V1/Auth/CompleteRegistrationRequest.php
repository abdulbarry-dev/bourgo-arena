<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\BaseFormRequest;

class CompleteRegistrationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $memberId = auth()->guard('sanctum')->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:members,email,'.$memberId],
            'phone' => ['required', 'string', 'max:20', 'unique:members,phone,'.$memberId],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', 'in:male,female'],
            'is_parent_account' => ['required', 'boolean'],
        ];
    }
}
