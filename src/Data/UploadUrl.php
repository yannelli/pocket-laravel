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
final readonly class UploadUrl implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public ?string $signedUrl = null,
        public ?string $recordingId = null,
        public ?string $contentType = null,
        public ?int $expiresIn = null,
        public ?DateTimeImmutable $expiresAt = null,
        public array $headers = [],
    ) {}

    /**
     * Create an UploadUrl instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $headers = $data['headers'] ?? [];

        return new self(
            signedUrl: $data['signed_url'] ?? $data['upload_url'] ?? $data['url'] ?? null,
            recordingId: $data['recording_id'] ?? $data['id'] ?? null,
            contentType: $data['content_type'] ?? null,
            expiresIn: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            expiresAt: isset($data['expires_at']) ? new DateTimeImmutable((string) $data['expires_at']) : null,
            headers: is_array($headers) ? $headers : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'signed_url' => $this->signedUrl,
            'recording_id' => $this->recordingId,
            'content_type' => $this->contentType,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt?->format('c'),
            'headers' => $this->headers !== [] ? $this->headers : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
