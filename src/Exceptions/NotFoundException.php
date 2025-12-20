<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class NotFoundException extends PocketException
{
    /**
     * Create a new NotFoundException instance.
     *
     * @param  string  $message  The exception message
     */
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}
