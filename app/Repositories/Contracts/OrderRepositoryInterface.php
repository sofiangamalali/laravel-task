<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get paginated orders with optional filters and page.
     */
    public function getPaginated(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Find order by ID or fail.
     */
    public function findById(int $id, array $with = []): ?Order;

    /**
     * Create a new order with items.
     */
    public function create(array $orderData, array $itemsData): Order;

    /**
     * Update order details.
     */
    public function update(Order $order, array $data, ?array $itemsData = null): Order;

    /**
     * Delete an order.
     */
    public function delete(Order $order): bool;
}
