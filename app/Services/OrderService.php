<?php
namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\CacheService;
use App\Models\Order;
use App\Models\User;
use App\Http\Resources\OrderResource;
use App\Events\OrderPlaced;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
	/**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * OrderService constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Get all orders with filters and pagination (cached)
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Create cache key
        $cacheKey = CacheService::generateOrdersCacheKey(Auth::id() ?? 0, [
            'filters' => $filters,
            'per_page' => $perPage,
            'page' => request()->get('page', 1)
        ]);

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($filters, $perPage) {
                return $this->orderRepository->getWithFilters($filters, $perPage);
            },
            ['orders']
        );
    }

    /**
     * Get orders for current user (cached)
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserOrders(int $perPage = 15): LengthAwarePaginator
    {
        $userId = Auth::id();

        // Create cache key
        $cacheKey = CacheService::generateOrdersCacheKey($userId, [
            'per_page' => $perPage,
            'page' => request()->get('page', 1)
        ]);

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($userId, $perPage) {
                return $this->orderRepository->getPaginatedByUser($userId, $perPage);
            },
            ['orders']
        );
    }

    /**
     * Get order by ID (cached)
     *
     * @param int $orderId
     * @return Order
     */
    public function getOrderById(int $orderId): Order
    {
        $cacheKey = "order:{$orderId}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($orderId) {
                return $this->orderRepository->findOrFail($orderId);
            },
            ['orders']
        );
    }

    /**
     * Create a new order
     *
     * @param array $data
     * @return Order
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Set user ID if not provided
            if (!isset($data['user_id'])) {
                $data['user_id'] = Auth::id();
            }

            // Validate products and calculate total
            $totalAmount = 0;
            $productsData = [];

            foreach ($data['products'] as $productData) {
                $product = $this->productRepository->findOrFail($productData['product_id']);

                // Check stock availability
                if (!$this->productRepository->hasStock($product->id, $productData['quantity'])) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $price = $product->price;
                $quantity = $productData['quantity'];
                $subtotal = $price * $quantity;
                $totalAmount += $subtotal;

                $productsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            // Create order
            $orderData = [
                'user_id' => $data['user_id'],
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ];

            $order = $this->orderRepository->create($orderData);

            // Create order products and update stock
            foreach ($productsData as $productData) {
                $order->orderProducts()->create([
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                    'subtotal' => $productData['subtotal'],
                ]);

                // Decrease product stock
                $this->productRepository->decreaseStock(
                    $productData['product_id'],
                    $productData['quantity']
                );
            }

            // Get the user who placed the order
            $user = User::find($data['user_id']);

            // Trigger the OrderPlaced event
            OrderPlaced::dispatch($order, $user, [
                'products_count' => count($productsData),
                'order_source' => $data['source'] ?? 'api',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $order;
        });
    }

    /**
     * Update an existing order
     *
     * @param int $orderId
     * @param array $data
     * @return Order
     */
    public function updateOrder(int $orderId, array $data): Order
    {
        $order = $this->orderRepository->findOrFail($orderId);

        // Only allow updating certain fields
        $allowedFields = ['status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        return $this->orderRepository->update($orderId, $updateData);
    }

    /**
     * Delete an order
     *
     * @param int $orderId
     * @return bool
     */
    public function deleteOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepository->findOrFail($orderId);

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                throw new \Exception('Only pending orders can be deleted');
            }

            // Restore product stock
            foreach ($order->orderProducts as $orderProduct) {
                $this->productRepository->increaseStock(
                    $orderProduct->product_id,
                    $orderProduct->quantity
                );
            }

            return $this->orderRepository->delete($orderId);
        });
    }

    /**
     * Update order status
     *
     * @param int $orderId
     * @param string $status
     * @return Order
     */
    public function updateOrderStatus(int $orderId, string $status): Order
    {
        return $this->orderRepository->updateStatus($orderId, $status);
    }

    /**
     * Cancel an order
     *
     * @param int $orderId
     * @return Order
     */
    public function cancelOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepository->findOrFail($orderId);

            // Only allow cancellation of pending orders
            if ($order->status !== 'pending') {
                throw new \Exception('Only pending orders can be cancelled');
            }

            // Restore product stock
            foreach ($order->orderProducts as $orderProduct) {
                $this->productRepository->increaseStock(
                    $orderProduct->product_id,
                    $orderProduct->quantity
                );
            }

            return $this->orderRepository->updateStatus($orderId, 'cancelled');
        });
    }

    /**
     * Complete an order
     *
     * @param int $orderId
     * @return Order
     */
    public function completeOrder(int $orderId): Order
    {
        $order = $this->orderRepository->findOrFail($orderId);

        // Only allow completion of pending orders
        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be completed');
        }

        return $this->orderRepository->updateStatus($orderId, 'completed');
    }

    /**
     * Get orders by status (cached)
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:status:{$status}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($status) {
                return $this->orderRepository->getByStatus($status);
            },
            ['orders']
        );
    }

    /**
     * Get user's orders by status (cached)
     *
     * @param int $userId
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserOrdersByStatus(int $userId, string $status): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:user:{$userId}:status:{$status}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($userId, $status) {
                return $this->orderRepository->getByUserAndStatus($userId, $status);
            },
            ['orders']
        );
    }

    /**
     * Get orders statistics (cached)
     *
     * @param int|null $userId
     * @return array
     */
    public function getOrdersStatistics(?int $userId = null): array
    {
        $cacheKey = "orders:statistics:" . ($userId ?? 'all');

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($userId) {
                return $this->orderRepository->getStatistics($userId);
            },
            ['orders']
        );
    }

    /**
     * Get monthly order statistics (cached)
     *
     * @param int $year
     * @param int|null $userId
     * @return array
     */
    public function getMonthlyOrderStatistics(int $year, ?int $userId = null): array
    {
        $cacheKey = "orders:monthly_stats:{$year}:" . ($userId ?? 'all');

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($year, $userId) {
                return $this->orderRepository->getMonthlyStatistics($year, $userId);
            },
            ['orders']
        );
    }

    /**
     * Get recent orders (cached)
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentOrders(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:recent:{$days}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($days) {
                return $this->orderRepository->getRecent($days);
            },
            ['orders']
        );
    }

    /**
     * Get top customers (cached)
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopCustomers(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:top_customers:{$limit}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($limit) {
                return $this->orderRepository->getTopCustomers($limit);
            },
            ['orders']
        );
    }

    /**
     * Get orders by date range (cached)
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersByDateRange(string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:date_range:" . md5($startDate . $endDate);

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($startDate, $endDate) {
                return $this->orderRepository->getByDateRange($startDate, $endDate);
            },
            ['orders']
        );
    }

    /**
     * Get orders by total amount range (cached)
     *
     * @param float $minAmount
     * @param float $maxAmount
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersByAmountRange(float $minAmount, float $maxAmount): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = "orders:amount_range:{$minAmount}:{$maxAmount}";

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($minAmount, $maxAmount) {
                return $this->orderRepository->getByTotalAmountRange($minAmount, $maxAmount);
            },
            ['orders']
        );
    }

    /**
     * Calculate total revenue (cached)
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    public function calculateTotalRevenue(?string $startDate = null, ?string $endDate = null): float
    {
        $cacheKey = "orders:revenue:" . md5(($startDate ?? '') . ($endDate ?? ''));

        return CacheService::remember(
            $cacheKey,
            config('cache.custom.orders.ttl', 300), // 5 minutes
            function () use ($startDate, $endDate) {
                return $this->orderRepository->calculateTotalRevenue($startDate, $endDate);
            },
            ['orders']
        );
    }

    /**
     * Get orders for API response
     *
     * @param array $filters
     * @param int $perPage
     * @return ResourceCollection
     */
    public function getOrdersForApi(array $filters = [], int $perPage = 15): ResourceCollection
    {
        $orders = $this->getAllOrders($filters, $perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Get single order for API response
     *
     * @param int $orderId
     * @return OrderResource
     */
    public function getOrderForApi(int $orderId): OrderResource
    {
        $order = $this->getOrderById($orderId);

        return new OrderResource($order);
    }

    /**
     * Get current user's orders for API response
     *
     * @param int $perPage
     * @return ResourceCollection
     */
    public function getUserOrdersForApi(int $perPage = 15): ResourceCollection
    {
        $orders = $this->getUserOrders($perPage);

        return OrderResource::collection($orders);
    }

    /**
     * Check if user owns order
     *
     * @param int $orderId
     * @param int $userId
     * @return bool
     */
    public function userOwnsOrder(int $orderId, int $userId): bool
    {
        $order = $this->orderRepository->find($orderId);

        return $order && $order->user_id === $userId;
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStatistics(): array
    {
        return CacheService::getCacheStats();
    }

    /**
     * Get statistics for a specific user's orders.
     *
     * @param int $userId
     * @return array
     */
    public function getUserOrderStatistics(int $userId): array
    {
        $orders = $this->orderRepository->getUserOrders($userId);

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'orders_by_status' => $orders->groupBy('status')
                ->map(fn($group) => $group->count()),
            'recent_orders' => $orders->take(5)->values(),
        ];
    }

    /**
     * Get statistics for all orders (admin only).
     *
     * @return array
     */
    public function getAllOrderStatistics(): array
    {
        $orders = $this->orderRepository->getAllOrders();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'orders_by_status' => $orders->groupBy('status')
                ->map(fn($group) => $group->count()),
            'orders_by_month' => $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m');
            })->map(fn($group) => [
                'count' => $group->count(),
                'revenue' => $group->sum('total_amount')
            ]),
            'top_customers' => $orders->groupBy('user_id')
                ->map(fn($group) => [
                    'user_id' => $group->first()->user_id,
                    'name' => $group->first()->user->name,
                    'orders_count' => $group->count(),
                    'total_spent' => $group->sum('total_amount')
                ])
                ->sortByDesc('total_spent')
                ->take(10)
                ->values(),
        ];
    }

    /**
     * Get monthly statistics for a specific user.
     *
     * @param int $userId
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getUserMonthlyStatistics(int $userId, int $year, int $month): array
    {
        $orders = $this->orderRepository->getUserOrdersByMonth($userId, $year, $month);

        return [
            'year' => $year,
            'month' => $month,
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'orders_by_status' => $orders->groupBy('status')
                ->map(fn($group) => $group->count()),
            'daily_orders' => $orders->groupBy(function($order) {
                return $order->created_at->format('Y-m-d');
            })->map(fn($group) => [
                'count' => $group->count(),
                'total' => $group->sum('total_amount')
            ]),
        ];
    }
}
