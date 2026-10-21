<?php

namespace App\Services;

use App\Exceptions\OrderCannotBeDeletedException;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    public function listOrders(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->orderRepository->getPaginated($filters, $perPage, $page);
    }

    public function getOrderById(int $id): Order
    {
        $order = $this->orderRepository->findById($id, ['items', 'payments', 'user']);

        if (!$order) {
            throw new NotFoundHttpException("Order with ID {$id} not found.");
        }

        return $order;
    }

    public function createOrder(array $data, int $userId): Order
    {
        $items = $data['items'] ?? [];

        $orderData = [
            'user_id' => $userId,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'status' => $data['status'] ?? Order::STATUS_PENDING,
        ];

        return $this->orderRepository->create($orderData, $items);
    }

    public function updateOrder(int $id, array $data): Order
    {
        $order = $this->getOrderById($id);

        $orderData = [];
        if (isset($data['customer_name'])) {
            $orderData['customer_name'] = $data['customer_name'];
        }
        if (isset($data['customer_email'])) {
            $orderData['customer_email'] = $data['customer_email'];
        }
        if (isset($data['customer_phone'])) {
            $orderData['customer_phone'] = $data['customer_phone'];
        }
        if (isset($data['status'])) {
            $orderData['status'] = $data['status'];
        }

        $items = $data['items'] ?? null;

        return $this->orderRepository->update($order, $orderData, $items);
    }

    public function deleteOrder(int $id): bool
    {
        $order = $this->getOrderById($id);

        if ($order->hasPayments()) {
            throw new OrderCannotBeDeletedException(
                "Cannot delete order #{$id} because it has associated payments."
            );
        }

        return $this->orderRepository->delete($order);
    }
}
