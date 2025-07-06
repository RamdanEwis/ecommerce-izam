<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     *
     * @param Order $order
     * @return void
     */
    public function created(Order $order): void
    {
        $this->clearOrdersCache($order->user_id);
        Log::info('Order created, cache cleared', ['order_id' => $order->id, 'user_id' => $order->user_id]);
    }

    /**
     * Handle the Order "updated" event.
     *
     * @param Order $order
     * @return void
     */
    public function updated(Order $order): void
    {
        $this->clearOrdersCache($order->user_id);
        Log::info('Order updated, cache cleared', ['order_id' => $order->id, 'user_id' => $order->user_id]);
    }

    /**
     * Handle the Order "deleted" event.
     *
     * @param Order $order
     * @return void
     */
    public function deleted(Order $order): void
    {
        $this->clearOrdersCache($order->user_id);
        Log::info('Order deleted, cache cleared', ['order_id' => $order->id, 'user_id' => $order->user_id]);
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param Order $order
     * @return void
     */
    public function restored(Order $order): void
    {
        $this->clearOrdersCache($order->user_id);
        Log::info('Order restored, cache cleared', ['order_id' => $order->id, 'user_id' => $order->user_id]);
    }

    /**
     * Handle the Order "force deleted" event.
     *
     * @param Order $order
     * @return void
     */
    public function forceDeleted(Order $order): void
    {
        $this->clearOrdersCache($order->user_id);
        Log::info('Order force deleted, cache cleared', ['order_id' => $order->id, 'user_id' => $order->user_id]);
    }

    /**
     * Clear order-related cache for a specific user.
     *
     * @param int $userId
     * @return void
     */
    private function clearOrdersCache(int $userId): void
    {
        CacheService::clearCacheByTags(['orders']);
    }
}
