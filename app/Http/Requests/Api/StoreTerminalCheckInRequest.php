<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\BaseFormRequest;

class StoreTerminalCheckInRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized by TerminalAuthMiddleware
    }

    protected function prepareForValidation(): void
    {
        // Handle ISAPI JSON payload format from Hikvision
        // Major type 5 = Event, Sub types: 75=Success, 76=Time segment, 1=No card, 38=Legal card, etc.
        if ($this->has('AccessControllerEvent')) {
            $isapiEvent = $this->input('AccessControllerEvent');
            $subType = $isapiEvent['subEventType'] ?? null;

            $result = in_array($subType, [75, 38]) ? 'authorized' : 'denied';
            $denialReason = null;

            if ($result === 'denied') {
                $denialReason = match ($subType) {
                    76 => 'expired_subscription', // Usually time segment violation
                    1 => 'invalid_card',
                    default => 'invalid_card'
                };
            }

            // Identify the user either by card UID or employee string (often used for PIN/ID entry)
            $uid = $isapiEvent['cardNo'] ?? $isapiEvent['employeeNoString'] ?? null;

            $this->merge([
                'card_uid' => $uid,
                'verify_mode' => $isapiEvent['currentVerifyMode'] ?? 'unknown',
                'result' => $result,
                'denial_reason' => $denialReason,
                'checked_in_at' => $this->input('dateTime') ?? now(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'card_uid' => ['required', 'string', 'max:255'],
            'verify_mode' => ['nullable', 'string'],
            'result' => ['required', 'in:authorized,denied'],
            'denial_reason' => ['nullable', 'in:expired_subscription,suspended_card,invalid_card,anti_passback'],
            'is_suspicious' => ['nullable', 'boolean'],
            'checked_in_at' => ['nullable', 'date'],
        ];
    }
}
