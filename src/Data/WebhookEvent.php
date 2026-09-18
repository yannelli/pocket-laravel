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
final readonly class WebhookEvent implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $user
     * @param  array<string, mixed>|null  $organization
     * @param  array<string, mixed>  $recording
     * @param  array<string, mixed>  $summarizations
     * @param  array<int, mixed>  $transcript
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $event,
        public ?DateTimeImmutable $timestamp = null,
        public array $user = [],
        public ?array $organization = null,
        public array $recording = [],
        public array $summarizations = [],
        public array $transcript = [],
        public array $raw = [],
    ) {}

    /**
     * Create a WebhookEvent instance from a decoded payload.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $timestamp = $data['timestamp'] ?? null;
        $user = $data['user'] ?? [];
        $organization = $data['organization'] ?? null;
        $recording = $data['recording'] ?? [];
        $summarizations = $data['summarizations'] ?? [];
        $transcript = $data['transcript'] ?? [];

        return new self(
            event: (string) ($data['event'] ?? ''),
            timestamp: $timestamp !== null ? new DateTimeImmutable((string) $timestamp) : null,
            user: is_array($user) ? $user : [],
            organization: is_array($organization) ? $organization : null,
            recording: is_array($recording) ? $recording : [],
            summarizations: is_array($summarizations) ? $summarizations : [],
            transcript: is_array($transcript) ? $transcript : [],
            raw: $data,
        );
    }

    /**
     * The recording ID included in the webhook payload, if present.
     */
    public function recordingId(): ?string
    {
        $id = $this->recording['id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'event' => $this->event,
            'timestamp' => $this->timestamp?->format('c'),
            'user' => $this->user !== [] ? $this->user : null,
            'organization' => $this->organization,
            'recording' => $this->recording !== [] ? $this->recording : null,
            'summarizations' => $this->summarizations !== [] ? $this->summarizations : null,
            'transcript' => $this->transcript !== [] ? $this->transcript : null,
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
