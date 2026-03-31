<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerminalCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized by TerminalAuthMiddleware
    }

    protected function prepareForValidation(): void
    {
        // Handle ISAPI JSON payload format from Hikvision
        if ($this->has('AccessControllerEvent')) {
            $isapiEvent = $this->input('AccessControllerEvent');
            $this->merge([
                'card_uid' => $isapiEvent['cardNo'] ?? null,
                'result' => ($isapiEvent['subEventType'] ?? null) === 75 ? 'authorized' : 'denied',
                'denial_reason' => ($isapiEvent['subEventType'] ?? null) !== 75 ? 'invalid_card' : null,
                'checked_in_at' => $this->input('dateTime') ?? now(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'card_uid' => ['required', 'string', 'max:255'],
            'result' => ['required', 'in:authorized,denied'],
            'denial_reason' => ['nullable', 'in:expired_subscription,suspended_card,invalid_card,anti_passback'],
            'is_suspicious' => ['nullable', 'boolean'],
            'checked_in_at' => ['nullable', 'date'],
        ];
    }
}
