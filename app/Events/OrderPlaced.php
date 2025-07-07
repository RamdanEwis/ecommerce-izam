<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced
{
    use Dispatchable, SerializesModels;

    /**
     * The order instance.
     */
    public Order $order;

    /**
     * The user instance.
     */
    public User $user;

    /**
     * Additional order data.
     */
    public array $orderData;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, User $user, array $orderData = [])
    {
        $this->order = $order;
        $this->user = $user;
        $this->orderData = $orderData;
    }
}
