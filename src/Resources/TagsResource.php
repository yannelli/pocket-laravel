<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Resources;

use PocketLabs\Pocket\Data\Tag;
use PocketLabs\Pocket\Exceptions\PocketException;
use PocketLabs\Pocket\PocketClient;

class TagsResource
{
    public function __construct(
        protected PocketClient $client
    ) {}

    /**
     * List all tags (ordered by usage count).
     *
     * @return array<Tag>
     *
     * @throws PocketException
     */
    public function list(): array
    {
        $response = $this->client->get('tags');

        return Tag::collection($response['data']);
    }

    /**
     * Alias for list().
     *
     * @return array<Tag>
     *
     * @throws PocketException
     */
    public function all(): array
    {
        return $this->list();
    }

    /**
     * Find a tag by ID.
     *
     * @throws PocketException
     */
    public function find(string $id): ?Tag
    {
        $tags = $this->list();

        foreach ($tags as $tag) {
            if ($tag->id === $id) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * Find a tag by name.
     *
     * @throws PocketException
     */
    public function findByName(string $name): ?Tag
    {
        $tags = $this->list();

        foreach ($tags as $tag) {
            if ($tag->name === $name) {
                return $tag;
            }
        }

        return null;
    }

    /**
     * Get the most used tags.
     *
     * @return array<Tag>
     *
     * @throws PocketException
     */
    public function mostUsed(int $limit = 10): array
    {
        $tags = $this->list();

        // Tags are already ordered by usage_count from the API
        return array_slice($tags, 0, $limit);
    }
}
