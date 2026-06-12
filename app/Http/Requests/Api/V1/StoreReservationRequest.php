<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('activity_slot_id') && ! $this->has('activity_session_id')) {
            $this->merge(['activity_session_id' => $this->input('activity_slot_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'exists:activities,id'],
            'activity_session_id' => [
                'required',
                Rule::exists('activity_sessions', 'id')->where(fn ($query) => $query->where('activity_id', $this->integer('activity_id'))),
            ],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['sometimes', 'string', 'in:konnect,loyalty'],
        ];
    }
}
