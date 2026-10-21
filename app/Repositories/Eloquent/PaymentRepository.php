<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function getPaginated(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = Payment::query()->with('order');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->latest()->paginate(perPage: $perPage, page: $page);
    }

    public function findById(int $id, array $with = ['order']): ?Payment
    {
        return Payment::with($with)->find($id);
    }

    public function getByOrderId(int $orderId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return Payment::with('order')
            ->where('order_id', $orderId)
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment->fresh();
    }
}
