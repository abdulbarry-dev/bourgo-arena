<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize legacy slot payloads.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slot_id') && ! $this->has('activity_slot_id')) {
            $this->merge(['activity_slot_id' => $this->input('slot_id')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'exists:activities,id'],
            'activity_slot_id' => [
                'required',
                Rule::exists('activity_slots', 'id')->where(fn ($query) => $query->where('activity_id', $this->integer('activity_id'))),
            ],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
