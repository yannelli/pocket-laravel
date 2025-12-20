<?php

declare(strict_types=1);

namespace Yannelli\Pocket;

use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\TagsResource;

class Pocket
{
    protected PocketClient $client;

    protected ?RecordingsResource $recordings = null;

    protected ?FoldersResource $folders = null;

    protected ?TagsResource $tags = null;

    /**
     * Create a new Pocket SDK instance.
     *
     * @param  string  $apiKey  The Pocket API key
     * @param  string  $baseUrl  The base URL for the Pocket API
     * @param  string  $apiVersion  The API version to use
     * @param  int  $timeout  Request timeout in seconds
     * @param  int  $retryTimes  Number of times to retry failed requests
     * @param  int  $retrySleep  Base sleep time in milliseconds between retries
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://production.heypocketai.com',
        string $apiVersion = 'v1',
        int $timeout = 30,
        int $retryTimes = 3,
        int $retrySleep = 1000
    ) {
        $this->client = new PocketClient(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            apiVersion: $apiVersion,
            timeout: $timeout,
            retryTimes: $retryTimes,
            retrySleep: $retrySleep
        );
    }

    /**
     * Create a new Pocket instance from config.
     *
     * @param  array{api_key?: string, base_url?: string, api_version?: string, timeout?: int, retry?: array{times?: int, sleep?: int}}  $config
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            apiKey: $config['api_key'] ?? '',
            baseUrl: $config['base_url'] ?? 'https://production.heypocketai.com',
            apiVersion: $config['api_version'] ?? 'v1',
            timeout: $config['timeout'] ?? 30,
            retryTimes: $config['retry']['times'] ?? 3,
            retrySleep: $config['retry']['sleep'] ?? 1000
        );
    }

    /**
     * Access the recordings resource.
     */
    public function recordings(): RecordingsResource
    {
        if ($this->recordings === null) {
            $this->recordings = new RecordingsResource($this->client);
        }

        return $this->recordings;
    }

    /**
     * Access the folders resource.
     */
    public function folders(): FoldersResource
    {
        if ($this->folders === null) {
            $this->folders = new FoldersResource($this->client);
        }

        return $this->folders;
    }

    /**
     * Access the tags resource.
     */
    public function tags(): TagsResource
    {
        if ($this->tags === null) {
            $this->tags = new TagsResource($this->client);
        }

        return $this->tags;
    }

    /**
     * Get the underlying HTTP client.
     */
    public function getClient(): PocketClient
    {
        return $this->client;
    }
}
