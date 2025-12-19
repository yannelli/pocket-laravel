<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Exceptions;

class ValidationException extends PocketException
{
    public function __construct(string $message = 'Validation failed', array $details = [])
    {
        parent::__construct($message, 400, $details);
    }
}
