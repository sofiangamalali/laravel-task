<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getIdentifier(): string
    {
        return 'paypal';
    }

    public function pay(PaymentRequestDTO $paymentRequest): PaymentResponseDTO
    {
        $payload = $paymentRequest->payload;
        $paypalEmail = $payload['paypal_email'] ?? null;

        // Simulate decline if email contains 'fail' or simulate_failure is true
        if (($paypalEmail && str_contains($paypalEmail, 'fail')) || !empty($payload['simulate_failure'])) {
            return PaymentResponseDTO::failed(
                message: 'PayPal payment failed: Insufficient account funds.',
                rawResponse: [
                    'gateway' => 'paypal',
                    'error' => 'INSTRUMENT_DECLINED',
                    'timestamp' => now()->toIso8601String(),
                ]
            );
        }

        $transactionId = 'PAYID-' . strtoupper(Str::random(14));

        return PaymentResponseDTO::success(
            transactionId: $transactionId,
            message: 'PayPal capture completed successfully.',
            rawResponse: [
                'gateway' => 'paypal',
                'capture_id' => $transactionId,
                'payer_email' => $paypalEmail ?? 'customer@example.com',
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'status' => 'COMPLETED',
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }
}
