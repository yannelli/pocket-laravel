<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

class RateLimitException extends PocketException
{
    /**
     * @var int|null
     */
    protected ?int $retryAfter;

    /**
     * Create a new RateLimitException instance.
     *
     * @param  string  $message  The exception message
     * @param  int|null  $retryAfter  Seconds to wait before retrying
     */
    public function __construct(string $message = 'Rate limit exceeded', ?int $retryAfter = null)
    {
        parent::__construct($message, 429);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the number of seconds to wait before retrying.
     *
     * @return int|null
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
