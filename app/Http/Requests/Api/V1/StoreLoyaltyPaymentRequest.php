<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['reservation', 'subscription'])],
            'id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => __('The payment type is required (reservation or subscription).'),
            'type.in' => __('The payment type must be either reservation or subscription.'),
            'id.required' => __('The item ID is required.'),
            'id.integer' => __('The item ID must be an integer.'),
        ];
    }
}
