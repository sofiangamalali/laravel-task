<?php

namespace App\Services\Payment\DTOs;

class PaymentResponseDTO
{
    public function __construct(
        public readonly bool $isSuccessful,
        public readonly string $status,
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
        public readonly array $rawResponse = []
    ) {}

    public static function success(string $transactionId, string $message = 'Payment processed successfully.', array $rawResponse = []): self
    {
        return new self(
            isSuccessful: true,
            status: 'successful',
            transactionId: $transactionId,
            message: $message,
            rawResponse: $rawResponse
        );
    }

    public static function failed(string $message = 'Payment processing failed.', array $rawResponse = [], ?string $transactionId = null): self
    {
        return new self(
            isSuccessful: false,
            status: 'failed',
            transactionId: $transactionId,
            message: $message,
            rawResponse: $rawResponse
        );
    }
}
