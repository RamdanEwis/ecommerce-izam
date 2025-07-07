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
use Illuminate\Support\Facades\Log;

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
     * Display a listing of all orders (admin only).
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
            Log::info('Creating new order', [
                'user_id' => $this->getAuthUserId(),
                'request_data' => $request->validated()
            ]);

            $order = $this->orderService->createOrder($request->validated());

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'user_id' => $this->getAuthUserId()
            ]);

            return ResponseBuilder::created(
                $this->orderService->getOrderForApi($order->id),
                'Order created successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create order', [
                'user_id' => $this->getAuthUserId(),
                'error' => $e->getMessage()
            ]);
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
            // Check if user owns the order or is admin
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId()) && !$this->getAuthUser()->isAdmin()) {
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
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId())) {
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
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId())) {
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
            $orders = $this->orderService->getUserOrdersForApi($this->getAuthUserId(), $perPage);

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
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId())) {
                Log::warning('Unauthorized order cancellation attempt', [
                    'order_id' => $id,
                    'user_id' => $this->getAuthUserId()
                ]);
                return ResponseBuilder::forbidden('You do not have permission to cancel this order');
            }

            Log::info('Cancelling order', [
                'order_id' => $id,
                'user_id' => $this->getAuthUserId()
            ]);

            $order = $this->orderService->cancelOrder($id);

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($order->id),
                'Order cancelled successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'order_id' => $id,
                'user_id' => $this->getAuthUserId(),
                'error' => $e->getMessage()
            ]);
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
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId())) {
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
     * Get statistics for the authenticated user's orders.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getUserOrderStatistics($this->getAuthUserId());
            return ResponseBuilder::success($statistics, 'Order statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order statistics');
        }
    }

    /**
     * Get all orders statistics (admin only).
     *
     * @return JsonResponse
     */
    public function allStatistics(): JsonResponse
    {
        try {
            $statistics = $this->orderService->getAllOrderStatistics();
            return ResponseBuilder::success($statistics, 'All order statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order statistics');
        }
    }

    /**
     * Get monthly statistics for the authenticated user's orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyStatistics(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year', date('Y'));
            $month = $request->get('month', date('m'));

            $statistics = $this->orderService->getUserMonthlyStatistics(
                $this->getAuthUserId(),
                $year,
                $month
            );
            return ResponseBuilder::success($statistics, 'Monthly statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve monthly statistics');
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
            $orders = $this->orderService->getUserOrdersByStatus(
                $this->getAuthUserId(),
                $status
            );

            return ResponseBuilder::collection($orders, "Orders with status '{$status}' retrieved successfully");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve orders by status');
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

            $orders = $this->orderService->getOrdersByDateRange(
                $request->get('start_date'),
                $request->get('end_date')
            );

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
                'max_amount' => 'required|numeric|gt:min_amount'
            ]);

            $orders = $this->orderService->getOrdersByAmountRange(
                $request->get('min_amount'),
                $request->get('max_amount')
            );

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
                'status' => 'required|in:pending,processing,completed,cancelled'
            ]);

            // Check if user owns the order
            if (!$this->orderService->userOwnsOrder($id, $this->getAuthUserId())) {
                return ResponseBuilder::forbidden('You do not have permission to update this order status');
            }

            $order = $this->orderService->updateOrderStatus($id, $request->get('status'));

            return ResponseBuilder::updated(
                $this->orderService->getOrderForApi($order->id),
                'Order status updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseBuilder::validationError($e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update order status');
        }
    }

    /**
     * Get total revenue.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function totalRevenue(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);
            $revenue = $this->orderService->getTotalRevenue($filters);

            return ResponseBuilder::success([
                'revenue' => $revenue
            ], 'Total revenue retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve total revenue');
        }
    }
}
