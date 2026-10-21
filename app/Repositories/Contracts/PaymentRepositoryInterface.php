<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    /**
     * Get paginated payments with filters and page.
     */
    public function getPaginated(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Find payment by ID.
     */
    public function findById(int $id, array $with = []): ?Payment;

    /**
     * Get payments for a specific order.
     */
    public function getByOrderId(int $orderId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Create a payment record.
     */
    public function create(array $data): Payment;

    /**
     * Update a payment record.
     */
    public function update(Payment $payment, array $data): Payment;
}
