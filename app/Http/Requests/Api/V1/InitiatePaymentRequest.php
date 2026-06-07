<?php

namespace App\Http\Requests\Api\V1;

use App\DTOs\PaymentInitiateDTO;
use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'reservation_id' => ['nullable', 'integer', 'exists:api_reservations,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
            'amount' => ['required', 'numeric', 'min:0'],

            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): PaymentInitiateDTO
    {
        return new PaymentInitiateDTO(
            memberId: $this->validated('member_id'),
            reservationId: $this->validated('reservation_id'),
            subscriptionId: $this->validated('subscription_id'),
            amount: $this->validated('amount'),

            description: $this->validated('description'),
            type: $this->validated('type'),
            paymentReference: null,
            metadata: null
        );
    }
}
