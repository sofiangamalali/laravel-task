<?php

namespace App\Services\Payment\DTOs;

class PaymentRequestDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $amount,
        public readonly string $paymentMethod,
        public readonly array $payload = [],
        public readonly ?string $currency = 'USD'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: (int) $data['order_id'],
            amount: (float) $data['amount'],
            paymentMethod: (string) $data['payment_method'],
            payload: $data['payload'] ?? [],
            currency: $data['currency'] ?? 'USD'
        );
    }
}
