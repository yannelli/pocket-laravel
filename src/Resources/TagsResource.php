<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use Yannelli\Pocket\Data\Tag;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class TagsResource
{
    /**
     * Create a new TagsResource instance.
     */
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
     * @param  string  $id  Tag ID
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
     * @param  string  $name  Tag name
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
     * @param  int  $limit  Maximum number of tags to return
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
