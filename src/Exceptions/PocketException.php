<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Exceptions;

use Exception;

class PocketException extends Exception
{
    protected array $details;

    public function __construct(
        string $message = '',
        int $code = 0,
        array $details = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function fromResponse(array $response, int $statusCode): self
    {
        $message = $response['error'] ?? 'An unknown error occurred';
        $details = $response['details'] ?? [];

        return new self($message, $statusCode, $details);
    }
}
