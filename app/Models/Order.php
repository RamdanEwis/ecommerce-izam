<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'user_id' => 'integer',
    ];

    /**
     * Valid order statuses
     */
    public const STATUSES = [
        'pending',
        'processing',
        'completed',
        'cancelled'
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for this order.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products')
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }

    /**
     * Get the order products for this order.
     */
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
