<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Exceptions;

class ServerException extends PocketException
{
    public function __construct(string $message = 'Internal server error')
    {
        parent::__construct($message, 500);
    }
}
