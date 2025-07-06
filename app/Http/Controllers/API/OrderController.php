<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * @var OrderService
     */
    protected OrderService $orderService;

    /**
     * OrderController Constructor
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the user's orders.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $orders = Order::with(['products', 'orderProducts'])
            ->where('user_id', Auth::id())
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created order in storage.
     *
     * @param StoreOrderRequest $request
     * @return OrderResource|\Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request): OrderResource|\Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();

            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => 0,
                'status' => $request->get('status', 'pending'),
            ]);

            $totalAmount = 0;
            $products = $request->get('products', []);

            foreach ($products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];

                // Check stock availability
                if ($product->stock < $quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                // Create order product
                $order->orderProducts()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);

                // Update product stock
                $product->decrement('stock', $quantity);

                $totalAmount += ($product->price * $quantity);
            }

            // Update order total
            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return new OrderResource($order->load(['products', 'orderProducts']));
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return response()->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return OrderResource|\Illuminate\Http\JsonResponse
     */
    public function show(int $id): OrderResource|\Illuminate\Http\JsonResponse
    {
        $order = Order::with(['products', 'orderProducts'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Update the specified order in storage.
     *
     * @param UpdateOrderRequest $request
     * @param int $id
     * @return OrderResource|\Illuminate\Http\JsonResponse
     */
    public function update(UpdateOrderRequest $request, int $id): OrderResource|\Illuminate\Http\JsonResponse
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            // Only allow status updates for completed orders
            if ($order->status === 'completed') {
                return response()->json(['error' => 'Cannot modify completed order'], Response::HTTP_FORBIDDEN);
            }

            $order->update($request->validated());

            return new OrderResource($order->load(['products', 'orderProducts']));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified order from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                return response()->json(['error' => 'Cannot delete non-pending order'], Response::HTTP_FORBIDDEN);
            }

            DB::beginTransaction();

            // Restore product stock
            foreach ($order->orderProducts as $orderProduct) {
                $orderProduct->product->increment('stock', $orderProduct->quantity);
            }

            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Order deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
