<?php

namespace App\Exceptions;

use Exception;

class OrderCannotBeDeletedException extends Exception
{
    public function __construct(string $message = 'Order cannot be deleted because it has associated payments.')
    {
        parent::__construct($message, 422);
    }
}
