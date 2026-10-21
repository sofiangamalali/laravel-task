<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
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

    public function test_user_can_create_order_and_total_is_calculated_automatically(): void
    {
        $payload = [
            'customer_name' => 'Alice Johnson',
            'customer_email' => 'alice@example.com',
            'customer_phone' => '+1555123456',
            'items' => [
                ['product_name' => 'Item A', 'quantity' => 2, 'price' => 25.50], // 51.00
                ['product_name' => 'Item B', 'quantity' => 3, 'price' => 10.00], // 30.00
            ],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertEquals(81.00, $response->json('data.total_amount'));

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Alice Johnson',
            'total_amount' => 81.00,
        ]);

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_order_creation_fails_without_items(): void
    {
        $payload = [
            'customer_name' => 'Bob',
            'items' => [],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_view_orders_and_filter_by_status(): void
    {
        Order::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->count(3)->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_update_order_details(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/orders/{$order->id}", [
                'customer_name' => 'Updated Name',
                'status' => 'confirmed',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.customer_name', 'Updated Name')
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_order_cannot_be_deleted_if_it_has_associated_payments(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => "Cannot delete order #{$order->id} because it has associated payments.",
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
