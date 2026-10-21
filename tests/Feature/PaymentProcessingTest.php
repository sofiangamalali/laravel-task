<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_payment_fails_if_order_is_not_confirmed(): void
    {
        // Business Rule: Payments can only be processed for orders with a confirmed status.
        $pendingOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/payments', [
                'order_id' => $pendingOrder->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => "Payment cannot be processed. Order status is 'pending', but only orders with 'confirmed' status can be paid.",
            ]);

        $this->assertDatabaseMissing('payments', ['order_id' => $pendingOrder->id]);
    }

    public function test_payment_succeeds_for_confirmed_order_using_credit_card(): void
    {
        $confirmedOrder = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total_amount' => 250.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/payments', [
                'order_id' => $confirmedOrder->id,
                'payment_method' => 'credit_card',
                'payload' => [
                    'card_number' => '4242424242424242',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'credit_card');

        $this->assertDatabaseHas('payments', [
            'order_id' => $confirmedOrder->id,
            'status' => 'successful',
            'payment_method' => 'credit_card',
        ]);
    }

    public function test_payment_succeeds_for_confirmed_order_using_paypal(): void
    {
        $confirmedOrder = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total_amount' => 175.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/payments', [
                'order_id' => $confirmedOrder->id,
                'payment_method' => 'paypal',
                'payload' => [
                    'paypal_email' => 'client@paypal.test',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'paypal');

        $this->assertDatabaseHas('payments', [
            'order_id' => $confirmedOrder->id,
            'payment_method' => 'paypal',
            'status' => 'successful',
        ]);
    }

    public function test_payment_handles_gateway_declined_failure_gracefully(): void
    {
        $confirmedOrder = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total_amount' => 99.00,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/payments', [
                'order_id' => $confirmedOrder->id,
                'payment_method' => 'credit_card',
                'payload' => [
                    'card_number' => '4000000000000000', // Trigger simulation decline
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.status', 'failed');

        $this->assertDatabaseHas('payments', [
            'order_id' => $confirmedOrder->id,
            'status' => 'failed',
        ]);
    }

    public function test_user_can_retrieve_payments_for_specific_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/orders/{$order->id}/payments");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_retrieve_supported_gateways(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/payments/gateways');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', ['credit_card', 'paypal', 'stripe']);
    }
}
