<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use Yannelli\Pocket\Data\Folder;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class FoldersResource
{
    /**
     * Create a new FoldersResource instance.
     *
     * @param  PocketClient  $client
     */
    public function __construct(
        protected PocketClient $client
    ) {}

    /**
     * List all folders.
     *
     * @return array<Folder>
     *
     * @throws PocketException
     */
    public function list(): array
    {
        $response = $this->client->get('folders');

        return Folder::collection($response['data']);
    }

    /**
     * Alias for list().
     *
     * @return array<Folder>
     *
     * @throws PocketException
     */
    public function all(): array
    {
        return $this->list();
    }

    /**
     * Find a folder by ID.
     *
     * @param  string  $id  Folder ID
     * @return Folder|null
     *
     * @throws PocketException
     */
    public function find(string $id): ?Folder
    {
        $folders = $this->list();

        foreach ($folders as $folder) {
            if ($folder->id === $id) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Find a folder by name.
     *
     * @param  string  $name  Folder name
     * @return Folder|null
     *
     * @throws PocketException
     */
    public function findByName(string $name): ?Folder
    {
        $folders = $this->list();

        foreach ($folders as $folder) {
            if ($folder->name === $name) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Get the default folder.
     *
     * @return Folder|null
     *
     * @throws PocketException
     */
    public function default(): ?Folder
    {
        $folders = $this->list();

        foreach ($folders as $folder) {
            if ($folder->isDefault) {
                return $folder;
            }
        }

        return null;
    }
}
