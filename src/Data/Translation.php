<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Translation implements Arrayable, JsonSerializable
{
    public function __construct(
        public ?string $id = null,
        public ?string $detectedLanguage = null,
        public ?string $toLanguage = null,
        public ?string $processingStatus = null,
        public ?string $errorMessage = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Create a Translation instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $createdAt = $data['created_at'] ?? null;
        $updatedAt = $data['updated_at'] ?? null;

        return new self(
            id: $data['id'] ?? null,
            detectedLanguage: $data['detected_language'] ?? null,
            toLanguage: $data['to_language'] ?? null,
            processingStatus: $data['processing_status'] ?? null,
            errorMessage: $data['error_message'] ?? null,
            createdAt: $createdAt !== null ? new DateTimeImmutable((string) $createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable((string) $updatedAt) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'detected_language' => $this->detectedLanguage,
            'to_language' => $this->toLanguage,
            'processing_status' => $this->processingStatus,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
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
