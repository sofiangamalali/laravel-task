<?php

namespace App\Http\Requests\Payment;

use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(PaymentGatewayManager $gatewayManager): array
    {
        $supportedGateways = $gatewayManager->getSupportedGateways();

        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string', Rule::in($supportedGateways)],
            'payload' => ['nullable', 'array'],
            'payload.card_number' => ['nullable', 'string'],
            'payload.card_exp_month' => ['nullable', 'numeric'],
            'payload.card_exp_year' => ['nullable', 'numeric'],
            'payload.card_cvv' => ['nullable', 'string'],
            'payload.paypal_email' => ['nullable', 'email'],
            'payload.simulate_failure' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'The order ID is required.',
            'order_id.exists' => 'The specified order does not exist.',
            'payment_method.required' => 'The payment method is required.',
            'payment_method.in' => 'Selected payment method is not supported.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation error.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
