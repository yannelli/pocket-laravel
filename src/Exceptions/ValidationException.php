<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class ValidationException extends PocketException
{
    /**
     * Create a new ValidationException instance.
     *
     * @param  string  $message  The exception message
     * @param  array<string, mixed>  $details  Validation error details
     */
    public function __construct(string $message = 'Validation failed', array $details = [])
    {
        parent::__construct($message, 400, $details);
    }
}
