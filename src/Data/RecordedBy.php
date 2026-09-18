<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class RecordedBy implements Arrayable, JsonSerializable
{
    public function __construct(
        public ?string $userId = null,
        public ?string $displayName = null,
        public ?string $email = null,
    ) {}

    /**
     * Create a RecordedBy instance from an array.
     *
     * @param  array{user_id?: string|null, display_name?: string|null, email?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            displayName: $data['display_name'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    /**
     * @return array{user_id?: string, display_name?: string, email?: string}
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'display_name' => $this->displayName,
            'email' => $this->email,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array{user_id?: string, display_name?: string, email?: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
