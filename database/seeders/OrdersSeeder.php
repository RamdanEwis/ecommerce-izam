<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('Creating 20 orders...');

        // Check if we have users and products
        $userCount = User::count();
        $productCount = Product::count();

        if ($userCount < 10) {
            $this->command->error('❌ Need at least 10 users to create orders. Run UsersSeeder first.');
            return;
        }

        if ($productCount < 50) {
            $this->command->error('❌ Need at least 50 products to create orders. Run ProductsSeeder first.');
            return;
        }

        // Disable mass assignment protection
        Order::unguard();
        OrderProduct::unguard();

        // Get some random users for orders
        $users = User::inRandomOrder()->limit(15)->get();

        for ($i = 1; $i <= 20; $i++) {
            $this->command->info("Creating order {$i}/20");

            DB::transaction(function () use ($users, $i) {
                // Select random user
                $user = $users->random();

                // Create order
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => 0, // Will be calculated
                    'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
                    'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                    'updated_at' => now(),
                ]);

                // Add 1-5 random products to the order
                $productCount = rand(1, 5);
                $products = Product::where('stock', '>', 0)
                    ->inRandomOrder()
                    ->limit($productCount)
                    ->get();

                $totalAmount = 0;

                foreach ($products as $product) {
                    $quantity = rand(1, 3);

                    // Make sure we don't exceed stock
                    $quantity = min($quantity, $product->stock);

                    if ($quantity > 0) {
                        // Create order product
                        OrderProduct::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'price' => $product->price, // Price at time of order
                        ]);

                        // Update product stock (reduce)
                        $product->decrement('stock', $quantity);

                        // Add to total
                        $totalAmount += ($product->price * $quantity);
                    }
                }

                // Update order total
                $order->update(['total_amount' => $totalAmount]);

                $this->command->info("Order {$order->id} created: {$productCount} products, total: $" . number_format($totalAmount, 2));
            });
        }

        // Re-enable mass assignment protection
        Order::reguard();
        OrderProduct::reguard();

        $this->command->info('✅ Successfully created 20 orders!');

        // Show summary
        $totalOrders = Order::count();
        $totalOrderProducts = OrderProduct::count();
        $totalOrderValue = Order::sum('total_amount');

        $this->command->info("📊 Summary:");
        $this->command->info("   Total Orders: {$totalOrders}");
        $this->command->info("   Total Order Items: {$totalOrderProducts}");
        $this->command->info("   Total Order Value: $" . number_format($totalOrderValue, 2));
    }
}
