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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:members,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:members,phone'],
            'is_parent_account' => ['required', 'boolean'],
        ];
    }
}
