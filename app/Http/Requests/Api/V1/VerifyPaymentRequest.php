<?php

namespace App\Http\Requests\Api\V1;

use App\DTOs\PaymentVerifyDTO;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_reference' => ['nullable', 'string'],
            'gateway_transaction_id' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): PaymentVerifyDTO
    {
        return new PaymentVerifyDTO(
            paymentReference: $this->validated('payment_reference'),
            gatewayTransactionId: $this->validated('gateway_transaction_id'),
        );
    }
}
