<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\PaymentRequestDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * Cache of resolved gateway instances.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $gateways = [];

    /**
     * Custom creator callbacks.
     *
     * @var array<string, callable>
     */
    protected array $customCreators = [];

    /**
     * Resolve a payment gateway strategy by its identifier.
     *
     * @throws InvalidArgumentException
     */
    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: config('payment.default', 'credit_card');

        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        return $this->gateways[$name] = $this->resolve($name);
    }

    /**
     * Process a payment using the appropriate gateway strategy.
     */
    public function process(PaymentRequestDTO $request): PaymentResponseDTO
    {
        $gateway = $this->gateway($request->paymentMethod);
        return $gateway->pay($request);
    }

    /**
     * Resolve the given gateway.
     */
    protected function resolve(string $name): PaymentGatewayInterface
    {
        if (isset($this->customCreators[$name])) {
            return call_user_func($this->customCreators[$name]);
        }

        $config = config("payment.gateways.{$name}");

        if (!$config || !isset($config['class'])) {
            throw new InvalidArgumentException("Unsupported payment gateway [{$name}]. Supported gateways: " . implode(', ', $this->getSupportedGateways()));
        }

        $class = $config['class'];

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Payment gateway class [{$class}] does not exist.");
        }

        $instance = new $class($config);

        if (!$instance instanceof PaymentGatewayInterface) {
            throw new InvalidArgumentException("Payment gateway [{$class}] must implement PaymentGatewayInterface.");
        }

        return $instance;
    }

    /**
     * Register a custom gateway creator callback.
     */
    public function extend(string $name, callable $callback): self
    {
        $this->customCreators[$name] = $callback;
        unset($this->gateways[$name]);
        return $this;
    }

    /**
     * Get list of supported gateway identifiers.
     *
     * @return array<string>
     */
    public function getSupportedGateways(): array
    {
        $configured = array_keys(config('payment.gateways', []));
        $custom = array_keys($this->customCreators);

        return array_values(array_unique(array_merge($configured, $custom)));
    }
}
