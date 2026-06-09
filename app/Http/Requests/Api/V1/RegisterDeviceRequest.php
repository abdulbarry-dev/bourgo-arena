<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedPlatforms = ['android', 'ios'];

        if (app()->environment(['local', 'testing'])) {
            $allowedPlatforms[] = 'web';
        }

        return [
            'device_id' => ['required', 'string', 'uuid'],
            'platform' => ['required', 'string', Rule::in($allowedPlatforms)],
            'app_version' => ['required', 'string', 'max:20'],
            'device_fingerprint' => ['nullable', 'array'],
            'device_fingerprint.model' => ['nullable', 'string', 'max:255'],
            'device_fingerprint.os_version' => ['nullable', 'string', 'max:255'],
            'device_fingerprint.locale' => ['nullable', 'string', 'max:10'],
            'device_fingerprint.timezone' => ['nullable', 'string', 'max:100'],
            'device_fingerprint.manufacturer' => ['nullable', 'string', 'max:255'],
            'integrity_token' => [
                Rule::requiredIf(fn () => $this->platform !== 'web'),
                'string',
                'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.unique' => 'This device has already been registered.',
        ];
    }
}
