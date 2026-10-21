<?php

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;

interface PaymentGatewayInterface
{
    /**
     * Process payment request through the gateway.
     */
    public function pay(PaymentRequestDTO $paymentRequest): PaymentResponseDTO;

    /**
     * Get unique identifier for the gateway (e.g. 'credit_card', 'paypal').
     */
    public function getIdentifier(): string;
}
