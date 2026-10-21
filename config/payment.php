<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Supported: "credit_card", "paypal", "stripe"
    |
    */
    'default' => env('DEFAULT_PAYMENT_GATEWAY', 'credit_card'),

    /*
    |--------------------------------------------------------------------------
    | Registered Payment Gateways (Strategy Pattern)
    |--------------------------------------------------------------------------
    |
    | Here you can map gateway identifier strings to their implementation classes.
    | To add a new gateway, simply create a class implementing
    | PaymentGatewayInterface and add it here.
    |
    */
    'gateways' => [
        'credit_card' => [
            'class' => \App\Services\Payment\Gateways\CreditCardGateway::class,
            'api_key' => env('CREDIT_CARD_API_KEY', 'cc_test_key_12345'),
            'secret' => env('CREDIT_CARD_SECRET', 'cc_test_secret_67890'),
            'sandbox' => env('CREDIT_CARD_SANDBOX', true),
        ],
        'paypal' => [
            'class' => \App\Services\Payment\Gateways\PayPalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID', 'paypal_sandbox_client_id'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET', 'paypal_sandbox_secret'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
        'stripe' => [
            'class' => \App\Services\Payment\Gateways\StripeGateway::class,
            'api_key' => env('STRIPE_SECRET_KEY', 'sk_test_123456789'),
        ],
    ],
];
