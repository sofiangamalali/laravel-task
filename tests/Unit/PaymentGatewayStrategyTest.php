<?php

namespace Tests\Unit;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\PaymentGatewayManager;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayStrategyTest extends TestCase
{
    public function test_credit_card_gateway_processes_successful_payment(): void
    {
        $gateway = new CreditCardGateway();
        $dto = new PaymentRequestDTO(
            orderId: 1,
            amount: 150.00,
            paymentMethod: 'credit_card',
            payload: ['card_number' => '4111111111111234']
        );

        $result = $gateway->pay($dto);

        $this->assertTrue($result->isSuccessful);
        $this->assertEquals('successful', $result->status);
        $this->assertStringStartsWith('CC-', $result->transactionId);
    }

    public function test_credit_card_gateway_fails_when_card_ends_in_zeroes(): void
    {
        $gateway = new CreditCardGateway();
        $dto = new PaymentRequestDTO(
            orderId: 1,
            amount: 150.00,
            paymentMethod: 'credit_card',
            payload: ['card_number' => '4000000000000000']
        );

        $result = $gateway->pay($dto);

        $this->assertFalse($result->isSuccessful);
        $this->assertEquals('failed', $result->status);
        $this->assertStringContainsString('declined', $result->message);
    }

    public function test_paypal_gateway_processes_successful_payment(): void
    {
        $gateway = new PayPalGateway();
        $dto = new PaymentRequestDTO(
            orderId: 2,
            amount: 85.50,
            paymentMethod: 'paypal',
            payload: ['paypal_email' => 'buyer@example.com']
        );

        $result = $gateway->pay($dto);

        $this->assertTrue($result->isSuccessful);
        $this->assertEquals('successful', $result->status);
        $this->assertStringStartsWith('PAYID-', $result->transactionId);
    }

    public function test_paypal_gateway_fails_when_email_indicates_failure(): void
    {
        $gateway = new PayPalGateway();
        $dto = new PaymentRequestDTO(
            orderId: 2,
            amount: 85.50,
            paymentMethod: 'paypal',
            payload: ['paypal_email' => 'fail_user@example.com']
        );

        $result = $gateway->pay($dto);

        $this->assertFalse($result->isSuccessful);
        $this->assertEquals('failed', $result->status);
    }

    public function test_stripe_gateway_processes_successful_payment(): void
    {
        $gateway = new StripeGateway();
        $dto = new PaymentRequestDTO(
            orderId: 3,
            amount: 200.00,
            paymentMethod: 'stripe'
        );

        $result = $gateway->pay($dto);

        $this->assertTrue($result->isSuccessful);
        $this->assertStringStartsWith('pi_', $result->transactionId);
    }

    public function test_manager_resolves_configured_gateways(): void
    {
        $manager = new PaymentGatewayManager();

        $this->assertInstanceOf(CreditCardGateway::class, $manager->gateway('credit_card'));
        $this->assertInstanceOf(PayPalGateway::class, $manager->gateway('paypal'));
        $this->assertInstanceOf(StripeGateway::class, $manager->gateway('stripe'));
    }

    public function test_manager_throws_exception_for_unsupported_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new PaymentGatewayManager();
        $manager->gateway('unknown_gateway_xyz');
    }

    public function test_manager_can_be_extended_with_new_gateway_at_runtime(): void
    {
        $manager = new PaymentGatewayManager();

        $mockCustomGateway = new class implements PaymentGatewayInterface {
            public function pay(PaymentRequestDTO $dto): PaymentResponseDTO {
                return PaymentResponseDTO::success('WALLET-999', 'Crypto/Wallet success');
            }
            public function getIdentifier(): string {
                return 'crypto_wallet';
            }
        };

        $manager->extend('crypto_wallet', fn () => $mockCustomGateway);

        $this->assertContains('crypto_wallet', $manager->getSupportedGateways());

        $dto = new PaymentRequestDTO(orderId: 5, amount: 50.0, paymentMethod: 'crypto_wallet');
        $result = $manager->process($dto);

        $this->assertTrue($result->isSuccessful);
        $this->assertEquals('WALLET-999', $result->transactionId);
    }
}
