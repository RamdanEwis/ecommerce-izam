<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Option 1: Quick seeding (default - small dataset)
        $this->seedQuickTestData();

        // Option 2: Large dataset seeding
        // Uncomment the line below to seed large datasets instead
        // $this->seedLargeDataset();

        $this->command->info('✅ Database seeding completed!');
    }

    /**
     * Quick seeding for testing (small dataset)
     */
    private function seedQuickTestData(): void
    {
        $this->command->info('🚀 Quick seeding: Small test dataset...');

        // Create test users
        User::factory(10)->create();

        // Create test products
        Product::factory(50)->create()->each(function ($product) {
            // Random stock between 10 and 100
            $product->stock = rand(10, 100);
            $product->save();
        });

        // Create test orders
        User::all()->each(function ($user) {
            // Create 1-5 orders per user
            $orderCount = rand(1, 5);
            for ($i = 0; $i < $orderCount; $i++) {
                $order = Order::factory()->create([
                    'user_id' => $user->id,
                    'status' => rand(0, 1) ? 'completed' : 'pending'
                ]);

                // Add 1-5 products to each order
                $products = Product::inRandomOrder()->take(rand(1, 5))->get();
                $totalAmount = 0;

                foreach ($products as $product) {
                    $quantity = rand(1, 5);
                    $price = $product->price;

                    OrderProduct::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price
                    ]);

                    $totalAmount += ($price * $quantity);
                }

                // Update order total
                $order->total_amount = $totalAmount;
                $order->save();
            }
        });
    }

    /**
     * Large dataset seeding (500 users, 10k products, 20 orders)
     */
    private function seedLargeDataset(): void
    {
        $this->command->info('🎯 Large dataset seeding: 500 users, 10k products, 20 orders...');

        $this->call([
            UsersSeeder::class,
            ProductsSeeder::class,
            OrdersSeeder::class,
        ]);
    }
}
