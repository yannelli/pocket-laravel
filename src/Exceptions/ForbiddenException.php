<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class ForbiddenException extends PocketException
{
    /**
     * Create a new ForbiddenException instance.
     *
     * @param  string  $message  The exception message
     */
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 403);
    }
}
