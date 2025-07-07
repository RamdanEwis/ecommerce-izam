<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('Creating 10,000 products...');

        // Disable mass assignment protection
        Product::unguard();

        // Temporarily disable Scout to speed up seeding
        Product::withoutSyncingToSearch(function () {
            // Use chunking to avoid memory issues
            $chunkSize = 500;
            $totalProducts = 10000;

            for ($i = 0; $i < $totalProducts; $i += $chunkSize) {
                $remaining = min($chunkSize, $totalProducts - $i);

                $this->command->info("Creating products batch: " . ($i + 1) . " to " . ($i + $remaining));

                // Create products in chunks with more diverse data
                $products = [];
                for ($j = 0; $j < $remaining; $j++) {
                    $products[] = [
                        'name' => fake()->words(rand(2, 4), true) . ' ' . fake()->randomElement([
                            'Laptop', 'Phone', 'Tablet', 'Watch', 'Camera', 'Speaker', 'Headphones',
                            'Monitor', 'Keyboard', 'Mouse', 'Gaming Chair', 'Desk', 'Router',
                            'Printer', 'Scanner', 'Drive', 'Card', 'Cable', 'Adapter', 'Charger'
                        ]),
                        'description' => fake()->paragraphs(rand(2, 4), true),
                        'price' => fake()->randomFloat(2, 10, 5000),
                        'stock' => fake()->numberBetween(0, 200),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Bulk insert for better performance
                DB::table('products')->insert($products);

                // Progress indicator
                $progress = round((($i + $remaining) / $totalProducts) * 100, 1);
                $this->command->info("Progress: {$progress}% (" . ($i + $remaining) . "/{$totalProducts})");
            }
        });

        // Re-enable mass assignment protection
        Product::reguard();

        $this->command->info('✅ Successfully created 10,000 products!');
        $this->command->info('📁 Indexing products for search...');

        // Re-index all products for Scout search
        Artisan::call('scout:import', ['model' => 'App\\Models\\Product']);

        $this->command->info('🔍 Products indexed for search successfully!');
    }
}
