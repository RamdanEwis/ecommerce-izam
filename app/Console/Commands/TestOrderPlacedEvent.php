<?php

namespace App\Console\Commands;

use App\Events\OrderPlaced;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;

class TestOrderPlacedEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:order-placed-event {--order-id=} {--user-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the OrderPlaced event and email notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->option('order-id');
        $userId = $this->option('user-id');

        if (!$orderId) {
            $orderId = $this->ask('Enter order ID:');
        }

        if (!$userId) {
            $userId = $this->ask('Enter user ID:');
        }

        try {
            $order = Order::findOrFail($orderId);
            $user = User::findOrFail($userId);

            $this->info("Testing OrderPlaced event for:");
            $this->line("Order ID: {$order->id}");
            $this->line("User: {$user->name} ({$user->email})");
            $this->line("Total Amount: {$order->total_amount}");
            $this->line("Status: {$order->status}");

            // Trigger the event
            $this->info("\nTriggering OrderPlaced event...");
            OrderPlaced::dispatch($order, $user, [
                'test' => true,
                'triggered_at' => now()->toISOString(),
            ]);

            $this->info("✅ OrderPlaced event dispatched successfully!");
            $this->info("📧 Email notification should be sent to admin");
            $this->info("📋 Check the logs for email details");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
