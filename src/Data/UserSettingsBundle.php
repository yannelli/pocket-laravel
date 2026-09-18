<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class UserSettingsBundle implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>|null  $notifications
     * @param  array<string, mixed>|null  $profile
     * @param  array<string, mixed>|null  $preferences
     * @param  array<int, Tag>  $tags
     */
    public function __construct(
        public ?OrganizationUser $user = null,
        public array $settings = [],
        public ?array $notifications = null,
        public ?array $profile = null,
        public ?array $preferences = null,
        public array $tags = [],
    ) {}

    /**
     * Create a UserSettingsBundle instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $user = $data['user'] ?? null;
        $tags = $data['tags'] ?? [];

        return new self(
            user: is_array($user) ? OrganizationUser::fromArray($user) : null,
            settings: is_array($data['settings'] ?? null) ? $data['settings'] : [],
            notifications: is_array($data['notifications'] ?? null) ? $data['notifications'] : null,
            profile: is_array($data['profile'] ?? null) ? $data['profile'] : null,
            preferences: is_array($data['preferences'] ?? null) ? $data['preferences'] : null,
            tags: is_array($tags) ? Tag::collection($tags) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user' => $this->user?->toArray(),
            'settings' => $this->settings !== [] ? $this->settings : null,
            'notifications' => $this->notifications,
            'profile' => $this->profile,
            'preferences' => $this->preferences,
            'tags' => $this->tags !== [] ? array_map(fn (Tag $tag) => $tag->toArray(), $this->tags) : null,
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
