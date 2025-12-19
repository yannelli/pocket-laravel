<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Exceptions;

class AuthenticationException extends PocketException
{
    public function __construct(string $message = 'Invalid API key')
    {
        parent::__construct($message, 401);
    }
}
