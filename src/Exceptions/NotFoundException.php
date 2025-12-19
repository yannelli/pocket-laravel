<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class NotFoundException extends PocketException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}
