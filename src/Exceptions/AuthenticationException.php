<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class AuthenticationException extends PocketException
{
    /**
     * Create a new AuthenticationException instance.
     *
     * @param  string  $message  The exception message
     */
    public function __construct(string $message = 'Invalid API key')
    {
        parent::__construct($message, 401);
    }
}
