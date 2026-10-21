<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => 'credit_card',
            'amount' => fake()->randomFloat(2, 20, 500),
            'status' => Payment::STATUS_SUCCESSFUL,
            'transaction_id' => 'CC-' . strtoupper(Str::random(12)),
            'gateway_response' => ['status' => 'approved'],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
            'transaction_id' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'error_message' => 'Transaction declined.',
        ]);
    }
}
