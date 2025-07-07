<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseBuilder;
use App\Services\OrderService;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * OrderController constructor.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'min_amount',
                'max_amount',
                'start_date',
                'end_date',
                'sort_by',
                'sort_direction'
            ]);

            $perPage = min($request->get('per_page', 15), 100);

            $orders = $this->orderService->getOrdersForApi($filters, $perPage);

            return ResponseBuilder::paginated($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve orders');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return ResponseBuilder::created(
                $this->orderService->getOrderForApi($order->id),
                'Order created successfully'
            );
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to create order');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to view this order');
            }

            $order = $this->orderService->getOrderForApi($id);

            return ResponseBuilder::success($order, 'Order retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to update this order');
            }

            $this->orderService->updateOrder($id, $request->validated());

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($id),
                'Order updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update order');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to delete this order');
            }

            $this->orderService->deleteOrder($id);

            return ResponseBuilder::deleted('Order deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to delete order');
        }
    }

    /**
     * Get user's orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myOrders(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);

            $orders = $this->orderService->getUserOrdersForApi($perPage);

            return ResponseBuilder::paginated($orders, 'Your orders retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve your orders');
        }
    }

    /**
     * Cancel an order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to cancel this order');
            }

            $order = $this->orderService->cancelOrder($id);

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($order->id),
                'Order cancelled successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to cancel order');
        }
    }

    /**
     * Complete an order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to complete this order');
            }

            $order = $this->orderService->completeOrder($id);

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($order->id),
                'Order completed successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to complete order');
        }
    }

    /**
     * Get orders by status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,completed,cancelled'
            ]);

            $status = $request->get('status');
            $orders = $this->orderService->getUserOrdersByStatus(Auth::id(), $status);

            return ResponseBuilder::collection($orders, "Orders with status '{$status}' retrieved successfully");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve orders by status');
        }
    }

    /**
     * Get order statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getOrdersStatistics(Auth::id());

            return ResponseBuilder::success($statistics, 'Order statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order statistics');
        }
    }

    /**
     * Get monthly order statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyStatistics(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'required|integer|min:2020|max:' . (date('Y') + 1)
            ]);

            $year = $request->get('year');
            $statistics = $this->orderService->getMonthlyOrderStatistics($year, Auth::id());

            return ResponseBuilder::success($statistics, 'Monthly order statistics retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve monthly order statistics');
        }
    }

    /**
     * Get recent orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'sometimes|integer|min:1|max:365'
            ]);

            $days = $request->get('days', 7);
            $orders = $this->orderService->getRecentOrders($days);

            return ResponseBuilder::collection($orders, 'Recent orders retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve recent orders');
        }
    }

    /**
     * Get orders by date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byDateRange(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $orders = $this->orderService->getOrdersByDateRange($startDate, $endDate);

            return ResponseBuilder::collection($orders, 'Orders by date range retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve orders by date range');
        }
    }

    /**
     * Get orders by amount range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function byAmountRange(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'min_amount' => 'required|numeric|min:0',
                'max_amount' => 'required|numeric|gte:min_amount'
            ]);

            $minAmount = $request->get('min_amount');
            $maxAmount = $request->get('max_amount');

            $orders = $this->orderService->getOrdersByAmountRange($minAmount, $maxAmount);

            return ResponseBuilder::collection($orders, 'Orders by amount range retrieved successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve orders by amount range');
        }
    }

    /**
     * Update order status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,completed,cancelled'
            ]);

            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, Auth::id())) {
                return ResponseBuilder::forbidden('You do not have permission to update this order');
            }

            $order = $this->orderService->updateOrderStatus($id, $request->get('status'));

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($order->id),
                'Order status updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update order status');
        }
    }

    /**
     * Calculate total revenue.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function totalRevenue(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date'
            ]);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $revenue = $this->orderService->calculateTotalRevenue($startDate, $endDate);

            return ResponseBuilder::success([
                'total_revenue' => $revenue,
                'start_date' => $startDate,
                'end_date' => $endDate
            ], 'Total revenue calculated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to calculate total revenue');
        }
    }
}
