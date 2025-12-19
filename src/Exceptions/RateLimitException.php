<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class RateLimitException extends PocketException
{
    protected ?int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', ?int $retryAfter = null)
    {
        parent::__construct($message, 429);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
