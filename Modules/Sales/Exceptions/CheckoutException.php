<?php

namespace Modules\Sales\Exceptions;

use Exception;

class CheckoutException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
