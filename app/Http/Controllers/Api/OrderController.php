<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OrderCannotBeDeletedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends Controller
{


    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * List all orders with optional status filter and pagination.
     */
    public function index(IndexOrderRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $filters = $request->only(['status', 'search']);

        $orders = $this->orderService->listOrders($filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'has_more_pages' => $orders->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Create a new order with items and auto-calculated total.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $userId = auth('api')->id();
        $order = $this->orderService->createOrder($request->validated(), $userId);

        return $this->successResponse(
            new OrderResource($order),
            'Order created successfully.',
            201
        );
    }

    /**
     * Display a specific order with items and payments.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);
            return $this->successResponse(new OrderResource($order), 'Order retrieved successfully.');
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Update an order.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder($id, $request->validated());
            return $this->successResponse(new OrderResource($order), 'Order updated successfully.');
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Delete an order (only if no payments are associated).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->orderService->deleteOrder($id);
            return $this->successResponse(null, 'Order deleted successfully.');
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (OrderCannotBeDeletedException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
