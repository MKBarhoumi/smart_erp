<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class TeifValidationException extends Exception
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(string $message, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}
