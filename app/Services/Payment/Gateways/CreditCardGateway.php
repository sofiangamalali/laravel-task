<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getIdentifier(): string
    {
        return 'credit_card';
    }

    public function pay(PaymentRequestDTO $paymentRequest): PaymentResponseDTO
    {
        // Simulation logic: fail if test flag or specific condition triggered
        $payload = $paymentRequest->payload;
        $cardNumber = $payload['card_number'] ?? null;

        // Simulate decline if card ends with 0000 or simulate_failure is true
        if (($cardNumber && str_ends_with($cardNumber, '0000')) || !empty($payload['simulate_failure'])) {
            return PaymentResponseDTO::failed(
                message: 'Credit card transaction declined by issuing bank.',
                rawResponse: [
                    'gateway' => 'credit_card',
                    'error_code' => 'CARD_DECLINED',
                    'timestamp' => now()->toIso8601String(),
                ]
            );
        }

        $transactionId = 'CC-' . strtoupper(Str::random(12));

        return PaymentResponseDTO::success(
            transactionId: $transactionId,
            message: 'Credit card charged successfully.',
            rawResponse: [
                'gateway' => 'credit_card',
                'transaction_id' => $transactionId,
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'status' => 'approved',
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }
}
