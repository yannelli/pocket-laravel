<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Exceptions;

use Exception;

class PocketException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    protected array $details;

    /**
     * Create a new PocketException instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code (usually HTTP status code)
     * @param  array<string, mixed>  $details  Additional error details
     * @param  Exception|null  $previous  The previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        array $details = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * Get additional error details.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Create a PocketException from an API response.
     *
     * @param  array{error?: string, details?: array<string, mixed>}  $response
     */
    public static function fromResponse(array $response, int $statusCode): self
    {
        $message = $response['error'] ?? 'An unknown error occurred';
        $details = $response['details'] ?? [];

        return new self($message, $statusCode, $details);
    }
}
