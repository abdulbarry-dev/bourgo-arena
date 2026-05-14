<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($this->user()->id),
            ],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('members', 'phone')->ignore($this->user()->id),
            ],
            'avatar_url' => ['sometimes', 'string', 'nullable'],
            'birth_date' => ['sometimes', 'date', 'nullable'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
        ];
    }

    /**
     * Map Flutter DTO field names back to database column names.
     *
     * @return array<string, mixed>
     */
    public function mappedData(): array
    {
        $data = $this->validated();

        if (isset($data['avatar_url'])) {
            $data['avatar'] = $data['avatar_url'];
            unset($data['avatar_url']);
        }

        if (isset($data['birth_date'])) {
            $data['date_of_birth'] = $data['birth_date'];
            unset($data['birth_date']);
        }

        return $data;
    }
}
