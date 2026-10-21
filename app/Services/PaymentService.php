<?php

namespace App\Services;

use App\Exceptions\InvalidOrderStatusForPaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentService
{
    public function __construct(
        protected PaymentRepositoryInterface $paymentRepository,
        protected OrderRepositoryInterface $orderRepository,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Process a payment for a specific order using Strategy Pattern.
     *
     * @throws InvalidOrderStatusForPaymentException
     * @throws NotFoundHttpException
     */
    public function processPayment(int $orderId, string $paymentMethod, array $payload = []): Payment
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new NotFoundHttpException("Order with ID {$orderId} not found.");
        }

        // Business Rule: Payments can ONLY be processed for orders with a confirmed status.
        if (!$order->isConfirmed()) {
            throw new InvalidOrderStatusForPaymentException(
                "Payment cannot be processed. Order status is '{$order->status}', but only orders with 'confirmed' status can be paid."
            );
        }

        // 1. Create initial pending payment record
        $payment = $this->paymentRepository->create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => $order->total_amount,
            'status' => Payment::STATUS_PENDING,
            'gateway_response' => null,
        ]);

        // 2. Prepare Payment Request DTO
        $paymentRequest = new PaymentRequestDTO(
            orderId: $order->id,
            amount: (float) $order->total_amount,
            paymentMethod: $paymentMethod,
            payload: $payload
        );

        // 3. Delegate to appropriate Strategy via PaymentGatewayManager
        $gatewayResult = $this->gatewayManager->process($paymentRequest);

        // 4. Update payment record with gateway result
        return $this->paymentRepository->update($payment, [
            'status' => $gatewayResult->isSuccessful ? Payment::STATUS_SUCCESSFUL : Payment::STATUS_FAILED,
            'transaction_id' => $gatewayResult->transactionId,
            'error_message' => !$gatewayResult->isSuccessful ? $gatewayResult->message : null,
            'gateway_response' => $gatewayResult->rawResponse,
        ]);
    }

    /**
     * Get paginated payments.
     */
    public function getPayments(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->paymentRepository->getPaginated($filters, $perPage, $page);
    }

    /**
     * Get payments for a specific order.
     */
    public function getPaymentsByOrder(int $orderId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new NotFoundHttpException("Order with ID {$orderId} not found.");
        }

        return $this->paymentRepository->getByOrderId($orderId, $perPage, $page);
    }

    /**
     * Get single payment by ID.
     */
    public function getPaymentById(int $id): Payment
    {
        $payment = $this->paymentRepository->findById($id);

        if (!$payment) {
            throw new NotFoundHttpException("Payment with ID {$id} not found.");
        }

        return $payment;
    }

    /**
     * Get supported payment gateways.
     */
    public function getSupportedGateways(): array
    {
        return $this->gatewayManager->getSupportedGateways();
    }
}
