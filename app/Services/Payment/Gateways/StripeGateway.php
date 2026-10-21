<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function pay(PaymentRequestDTO $paymentRequest): PaymentResponseDTO
    {
        $payload = $paymentRequest->payload;

        if (!empty($payload['simulate_failure'])) {
            return PaymentResponseDTO::failed(
                message: 'Stripe charge failed: PaymentIntent authentication required.',
                rawResponse: [
                    'gateway' => 'stripe',
                    'code' => 'authentication_required',
                    'timestamp' => now()->toIso8601String(),
                ]
            );
        }

        $transactionId = 'pi_' . strtolower(Str::random(24));

        return PaymentResponseDTO::success(
            transactionId: $transactionId,
            message: 'Stripe charge succeeded.',
            rawResponse: [
                'gateway' => 'stripe',
                'charge_id' => 'ch_' . strtolower(Str::random(24)),
                'payment_intent' => $transactionId,
                'amount' => $paymentRequest->amount,
                'currency' => $paymentRequest->currency,
                'status' => 'succeeded',
                'timestamp' => now()->toIso8601String(),
            ]
        );
    }
}
