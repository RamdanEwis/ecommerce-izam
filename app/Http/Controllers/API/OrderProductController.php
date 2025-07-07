<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseBuilder;
use App\Http\Requests\OrderProductRequest;
use App\Services\OrderProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderProductController extends Controller
{
    /**
     * @var OrderProductService
     */
    protected OrderProductService $orderProductService;

    /**
     * OrderProductController constructor.
     *
     * @param OrderProductService $orderProductService
     */
    public function __construct(OrderProductService $orderProductService)
    {
        $this->orderProductService = $orderProductService;
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->except(['index', 'show']);
    }

    /**
     * Display a listing of order products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);
            $orderProducts = $this->orderProductService->getAll($perPage);

            return ResponseBuilder::paginated($orderProducts, 'Order products retrieved successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order products');
        }
    }

    /**
     * Store a newly created order product.
     *
     * @param OrderProductRequest $request
     * @return JsonResponse
     */
    public function store(OrderProductRequest $request): JsonResponse
    {
        try {
            $orderProduct = $this->orderProductService->save($request->validated());

            return ResponseBuilder::created($orderProduct, 'Order product created successfully');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to create order product');
        }
    }

    /**
     * Display the specified order product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $orderProduct = $this->orderProductService->getById($id);

            return ResponseBuilder::success($orderProduct, 'Order product retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to retrieve order product');
        }
    }

    /**
     * Update the specified order product.
     *
     * @param OrderProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(OrderProductRequest $request, int $id): JsonResponse
    {
        try {
            $orderProduct = $this->orderProductService->update($request->validated(), $id);

            return ResponseBuilder::updated($orderProduct, 'Order product updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to update order product');
        }
    }

    /**
     * Remove the specified order product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->orderProductService->deleteById($id);

            return ResponseBuilder::deleted('Order product deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseBuilder::notFound('Order product not found');
        } catch (\Exception $e) {
            return ResponseBuilder::exception($e, 'Failed to delete order product');
        }
    }
}
