<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => bcrypt('password123'),
            ]
        );

        // Confirmed Order with Items
        $confirmedOrder = \App\Models\Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Ahmed Mohamed',
            'customer_email' => 'ahmed@example.com',
            'customer_phone' => '+201001234567',
            'total_amount' => 350.00,
            'status' => \App\Models\Order::STATUS_CONFIRMED,
        ]);

        $confirmedOrder->items()->createMany([
            ['product_name' => 'Laptop Stand', 'quantity' => 1, 'price' => 50.00, 'subtotal' => 50.00],
            ['product_name' => 'Mechanical Keyboard', 'quantity' => 2, 'price' => 150.00, 'subtotal' => 300.00],
        ]);

        // Pending Order with Items
        $pendingOrder = \App\Models\Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Sarah Smith',
            'customer_email' => 'sarah@example.com',
            'customer_phone' => '+1234567890',
            'total_amount' => 120.00,
            'status' => \App\Models\Order::STATUS_PENDING,
        ]);

        $pendingOrder->items()->createMany([
            ['product_name' => 'Wireless Mouse', 'quantity' => 2, 'price' => 60.00, 'subtotal' => 120.00],
        ]);
    }
}
