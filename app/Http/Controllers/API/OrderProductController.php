<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderProductRequest;
use App\Http\Resources\OrderProductResource;
use App\Services\OrderProductService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderProductController extends Controller
{
    /**
     * @var OrderProductService
     */
    protected OrderProductService $orderProductService;

    /**
     * DummyModel Constructor
     *
     * @param OrderProductService $orderProductService
     *
     */
    public function __construct(OrderProductService $orderProductService)
    {
        $this->orderProductService = $orderProductService;
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return OrderProductResource::collection($this->orderProductService->getAll());
    }

    public function store(OrderProductRequest $request): OrderProductResource|\Illuminate\Http\JsonResponse
    {
        try {
            return new OrderProductResource($this->orderProductService->save($request->validated()));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): OrderProductResource
    {
        return OrderProductResource::make($this->orderProductService->getById($id));
    }

    public function update(OrderProductRequest $request, int $id): OrderProductResource|\Illuminate\Http\JsonResponse
    {
        try {
            return new OrderProductResource($this->orderProductService->update($request->validated(), $id));
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->orderProductService->deleteById($id);
            return response()->json(['message' => 'Deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['error' => 'There is an error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
