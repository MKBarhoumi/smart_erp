<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvoiceStateException extends Exception
{
    public function __construct(string $message = 'Invalid invoice state transition')
    {
        parent::__construct($message);
    }
}
