<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidOrderStatusForPaymentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\IndexPaymentRequest;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentController extends Controller
{


    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Process payment for an order using the selected gateway strategy.
     */
    public function process(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment(
                orderId: (int) $request->input('order_id'),
                paymentMethod: (string) $request->input('payment_method'),
                payload: (array) $request->input('payload', [])
            );

            $statusMessage = $payment->isSuccessful()
                ? 'Payment processed successfully.'
                : 'Payment processing failed: ' . ($payment->error_message ?? 'Transaction declined.');

            $httpStatus = $payment->isSuccessful() ? 200 : 422;

            return response()->json([
                'success' => $payment->isSuccessful(),
                'message' => $statusMessage,
                'data' => new PaymentResource($payment),
            ], $httpStatus);

        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (InvalidOrderStatusForPaymentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * View all payments with optional filtering and pagination.
     */
    public function index(IndexPaymentRequest $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $filters = $request->only(['status', 'order_id', 'payment_method']);

        $payments = $this->paymentService->getPayments($filters, $perPage, $page);

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data' => PaymentResource::collection($payments->items()),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'has_more_pages' => $payments->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Retrieve payment details for a specific order.
     */
    public function byOrder(int $orderId): JsonResponse
    {
        try {
            $perPage = (int) request()->input('per_page', 15);
            $page = (int) request()->input('page', 1);
            $payments = $this->paymentService->getPaymentsByOrder($orderId, $perPage, $page);

            return response()->json([
                'success' => true,
                'message' => "Payments for order #{$orderId} retrieved successfully.",
                'data' => PaymentResource::collection($payments->items()),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'has_more_pages' => $payments->hasMorePages(),
                ],
            ], 200);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * View single payment details.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->getPaymentById($id);
            return $this->successResponse(new PaymentResource($payment), 'Payment details retrieved.');
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * List all supported payment gateway strategies.
     */
    public function gateways(): JsonResponse
    {
        $gateways = $this->paymentService->getSupportedGateways();
        return $this->successResponse($gateways, 'Supported payment gateways retrieved.');
    }
}
