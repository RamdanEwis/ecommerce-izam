<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationToAdmin implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'emails';

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $user = $event->user;

        try {
            // Load order with products for detailed email
            $order->load('products', 'orderProducts');

            // Prepare email data
            $emailData = [
                'order_id' => $order->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                'products' => $order->products->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'quantity' => $product->pivot->quantity,
                        'price' => $product->pivot->price,
                        'total' => $product->pivot->quantity * $product->pivot->price,
                    ];
                })->toArray(),
            ];

            // Log that we're sending the email
            Log::info('Sending admin email notification', [
                'order_id' => $order->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'total_amount' => $order->total_amount,
                'products_count' => $order->products->count(),
            ]);

            // Here you would send the actual email
             Mail::to(config('mail.admin_email'))->send(new OrderPlacedNotification($emailData));

            // For now, we'll just log that the email would be sent
            Log::info('Admin email notification would be sent', [
                'to' => config('mail.admin_email', 'admin@example.com'),
                'subject' => 'New Order Placed - Order #' . $order->id,
                'order_data' => $emailData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send admin notification for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw the exception to trigger queue retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderPlaced $event, \Throwable $exception): void
    {
        Log::error('Admin notification failed permanently', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
