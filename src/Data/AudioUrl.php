<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class AudioUrl implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $signedUrl,
        public int $expiresIn,
        public DateTimeImmutable $expiresAt,
    ) {}

    /**
     * Create an AudioUrl instance from an array.
     *
     * @param  array{signed_url: string, expires_in: int, expires_at: string}  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signedUrl: $data['signed_url'],
            expiresIn: $data['expires_in'],
            expiresAt: new DateTimeImmutable($data['expires_at']),
        );
    }

    /**
     * Check if the signed URL has expired.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable;
    }

    /**
     * Get the number of seconds until the URL expires.
     */
    public function secondsUntilExpiry(): int
    {
        $diff = $this->expiresAt->getTimestamp() - time();

        return max(0, $diff);
    }

    /**
     * @return array{signed_url: string, expires_in: int, expires_at: string}
     */
    public function toArray(): array
    {
        return [
            'signed_url' => $this->signedUrl,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt->format('c'),
        ];
    }

    /**
     * @return array{signed_url: string, expires_in: int, expires_at: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
