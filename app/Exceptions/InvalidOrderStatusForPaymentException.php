<?php

namespace App\Exceptions;

use Exception;

class InvalidOrderStatusForPaymentException extends Exception
{
    public function __construct(string $message = 'Payments can only be processed for orders with a confirmed status.')
    {
        parent::__construct($message, 422);
    }
}
