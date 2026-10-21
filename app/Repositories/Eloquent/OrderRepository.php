<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = Order::query()->with(['items', 'payments', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        return $query->latest()->paginate(perPage: $perPage, page: $page);
    }

    public function findById(int $id, array $with = ['items', 'payments']): ?Order
    {
        return Order::with($with)->find($id);
    }

    public function create(array $orderData, array $itemsData): Order
    {
        return DB::transaction(function () use ($orderData, $itemsData) {
            $totalAmount = 0;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $price = (float) $item['price'];
                $quantity = (int) $item['quantity'];
                $subtotal = round($price * $quantity, 2);
                $totalAmount += $subtotal;

                $preparedItems[] = [
                    'product_name' => $item['product_name'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $orderData['total_amount'] = round($totalAmount, 2);
            $order = Order::create($orderData);

            foreach ($preparedItems as $item) {
                $order->items()->create($item);
            }

            return $order->load('items');
        });
    }

    public function update(Order $order, array $data, ?array $itemsData = null): Order
    {
        return DB::transaction(function () use ($order, $data, $itemsData) {
            if ($itemsData !== null) {
                // Remove existing items and recreate
                $order->items()->delete();

                $totalAmount = 0;
                foreach ($itemsData as $item) {
                    $price = (float) $item['price'];
                    $quantity = (int) $item['quantity'];
                    $subtotal = round($price * $quantity, 2);
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_name' => $item['product_name'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);
                }

                $data['total_amount'] = round($totalAmount, 2);
            }

            $order->update($data);

            return $order->fresh(['items', 'payments']);
        });
    }

    public function delete(Order $order): bool
    {
        return (bool) $order->delete();
    }
}
