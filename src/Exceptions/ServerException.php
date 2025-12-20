<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class ServerException extends PocketException
{
    /**
     * Create a new ServerException instance.
     *
     * @param  string  $message  The exception message
     */
    public function __construct(string $message = 'Internal server error')
    {
        parent::__construct($message, 500);
    }
}
