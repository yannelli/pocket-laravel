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
        public ?string $signedUrl = null,
        public ?int $expiresIn = null,
        public ?DateTimeImmutable $expiresAt = null,
    ) {}

    /**
     * Create an AudioUrl instance from an array.
     *
     * @param  array{signed_url?: string|null, expires_in?: int|null, expires_at?: string|null}  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signedUrl: $data['signed_url'] ?? null,
            expiresIn: $data['expires_in'] ?? null,
            expiresAt: isset($data['expires_at']) ? new DateTimeImmutable($data['expires_at']) : null,
        );
    }

    /**
     * Check if the signed URL has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }

        return $this->expiresAt < new DateTimeImmutable;
    }

    /**
     * Get the number of seconds until the URL expires.
     */
    public function secondsUntilExpiry(): int
    {
        if ($this->expiresAt === null) {
            return 0;
        }

        $diff = $this->expiresAt->getTimestamp() - time();

        return max(0, $diff);
    }

    /**
     * @return array{signed_url: string|null, expires_in: int|null, expires_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'signed_url' => $this->signedUrl,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt?->format('c'),
        ];
    }

    /**
     * @return array{signed_url: string|null, expires_in: int|null, expires_at: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
