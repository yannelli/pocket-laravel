<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use Yannelli\Pocket\Data\PaginatedUsers;
use Yannelli\Pocket\Data\UserSettingsBundle;
use Yannelli\Pocket\Data\UserSettingsUpdate;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class UsersResource
{
    /**
     * Create a new UsersResource instance.
     */
    public function __construct(
        protected PocketClient $client
    ) {}

    /**
     * List active organization members.
     *
     * Requires an organization API key with the `users:read` scope.
     *
     * @throws PocketException
     */
    public function list(?string $q = null, int $page = 1, int $limit = 25): PaginatedUsers
    {
        $response = $this->client->get('users', [
            'q' => $q,
            'page' => max($page, 1),
            'limit' => max(1, min($limit, 100)),
        ]);

        return PaginatedUsers::fromArray($response);
    }

    /**
     * Get an organization member's settings bundle.
     *
     * Requires an organization API key with the `users:read` scope.
     *
     * @param  array<int, string>  $include  Optional sections: profile, preferences, tags
     *
     * @throws PocketException
     */
    public function settings(
        string $userId,
        array $include = [],
        ?bool $includeProfile = null,
        ?bool $includePreferences = null,
        ?bool $includeTags = null,
        ?bool $includeNotifications = null,
        ?int $preferencesPage = null,
        ?int $preferencesLimit = null,
    ): UserSettingsBundle {
        $query = [
            'include' => $include !== [] ? implode(',', $include) : null,
            'include_profile' => $this->formatOptionalBoolean($includeProfile),
            'include_preferences' => $this->formatOptionalBoolean($includePreferences),
            'include_tags' => $this->formatOptionalBoolean($includeTags),
            'include_notifications' => $this->formatOptionalBoolean($includeNotifications),
            'preferences_page' => $preferencesPage,
            'preferences_limit' => $preferencesLimit === null ? null : max(1, min($preferencesLimit, 200)),
        ];

        $response = $this->client->get('users/'.rawurlencode($userId).'/settings', $query);

        /** @var array<string, mixed> $data */
        $data = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return UserSettingsBundle::fromArray($data);
    }

    /**
     * Patch an organization member's settings bundle.
     *
     * Requires an organization API key with the `users:write` scope.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws PocketException
     */
    public function updateSettings(string $userId, array $payload): UserSettingsUpdate
    {
        $response = $this->client->patch('users/'.rawurlencode($userId).'/settings', $payload);

        return UserSettingsUpdate::fromArray($response);
    }

    protected function formatOptionalBoolean(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'true' : 'false';
    }
}
