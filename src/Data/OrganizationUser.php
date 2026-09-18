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
final readonly class OrganizationUser implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $userId,
        public ?string $email = null,
        public ?string $displayName = null,
        public ?string $role = null,
        public ?string $status = null,
        public ?bool $isActive = null,
        public ?string $timezone = null,
        public ?int $recordingCount = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
        public ?DateTimeImmutable $memberSince = null,
        public ?DateTimeImmutable $latestRecordingAt = null,
    ) {}

    /**
     * Create an OrganizationUser instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (string) ($data['user_id'] ?? $data['id'] ?? ''),
            email: $data['email'] ?? null,
            displayName: $data['display_name'] ?? null,
            role: $data['role'] ?? null,
            status: $data['status'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            timezone: $data['timezone'] ?? null,
            recordingCount: isset($data['recording_count']) ? (int) $data['recording_count'] : null,
            createdAt: self::optionalDate($data['created_at'] ?? null),
            updatedAt: self::optionalDate($data['updated_at'] ?? null),
            memberSince: self::optionalDate($data['member_since'] ?? null),
            latestRecordingAt: self::optionalDate($data['latest_recording_at'] ?? null),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, OrganizationUser>
     *
     * @throws Exception
     */
    public static function collection(array $items): array
    {
        return array_values(array_map(fn (array $item) => self::fromArray($item), $items));
    }

    /**
     * @throws Exception
     */
    private static function optionalDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return new DateTimeImmutable((string) $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'email' => $this->email,
            'display_name' => $this->displayName,
            'role' => $this->role,
            'status' => $this->status,
            'is_active' => $this->isActive,
            'timezone' => $this->timezone,
            'recording_count' => $this->recordingCount,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
            'member_since' => $this->memberSince?->format('c'),
            'latest_recording_at' => $this->latestRecordingAt?->format('c'),
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
