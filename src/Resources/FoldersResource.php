<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use Exception;
use Yannelli\Pocket\Data\Folder;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class FoldersResource
{
    /**
     * Create a new FoldersResource instance.
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
     * @throws Exception
     */
    public function list(): array
    {
        $response = $this->client->get('folders');

        return Folder::collection(is_array($response['data'] ?? null) ? $response['data'] : []);
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
     *
     * @throws PocketException
     */
    public function find(string $id): ?Folder
    {
        $folders = $this->list();

        foreach ($this->flatten($folders) as $folder) {
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
     *
     * @throws PocketException
     */
    public function findByName(string $name): ?Folder
    {
        $folders = $this->list();

        foreach ($this->flatten($folders) as $folder) {
            if ($folder->name === $name) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Get the default folder.
     *
     *
     * @throws PocketException
     */
    public function default(): ?Folder
    {
        $folders = $this->list();

        foreach ($this->flatten($folders) as $folder) {
            if ($folder->isDefault) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * Flatten a folder tree into a single list.
     *
     * @param  array<int, Folder>  $folders
     * @return array<int, Folder>
     */
    public function flatten(array $folders): array
    {
        $flat = [];

        foreach ($folders as $folder) {
            $flat = array_merge($flat, $folder->flatten());
        }

        return $flat;
    }
}
