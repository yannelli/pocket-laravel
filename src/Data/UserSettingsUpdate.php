<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class UserSettingsUpdate implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $applied
     */
    public function __construct(
        public bool $success,
        public array $applied = [],
        public ?int $profileVersion = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $applied = $data['applied'] ?? [];

        return new self(
            success: (bool) ($data['success'] ?? true),
            applied: is_array($applied) ? $applied : [],
            profileVersion: isset($data['profile_version']) ? (int) $data['profile_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'applied' => $this->applied !== [] ? $this->applied : null,
            'profile_version' => $this->profileVersion,
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
