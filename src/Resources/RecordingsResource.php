<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use DateTimeInterface;
use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yannelli\Pocket\Data\PaginatedRecordings;
use Yannelli\Pocket\Data\Recording;
use Yannelli\Pocket\Data\UploadUrl;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class RecordingsResource
{
    /**
     * Create a new RecordingsResource instance.
     */
    public function __construct(
        protected PocketClient $client
    ) {}

    private mixed $rawResponse = null;

    /**
     * List recordings with optional filters.
     *
     * @param  string|null  $folderId  Filter by folder ID
     * @param  DateTimeInterface|string|null  $startDate  Filter recordings from this date
     * @param  DateTimeInterface|string|null  $endDate  Filter recordings until this date
     * @param  array<string>  $tagIds  Filter by tag IDs
     * @param  int  $page  Page number
     * @param  int  $limit  Items per page (max 100)
     *
     * @throws PocketException
     * @throws Exception
     */
    public function list(
        ?string $folderId = null,
        DateTimeInterface|string|null $startDate = null,
        DateTimeInterface|string|null $endDate = null,
        array $tagIds = [],
        int $page = 1,
        int $limit = 20
    ): PaginatedRecordings {
        $query = [
            'folder_id' => $folderId,
            'start_date' => $this->formatDate($startDate),
            'end_date' => $this->formatDate($endDate),
            'tag_ids' => count($tagIds) > 0 ? implode(',', $tagIds) : null,
            'page' => max($page, 1),
            'limit' => max(1, min($limit, 100)),
        ];

        $response = $this->client->get('recordings', $query);

        $this->rawResponse = $response;

        return PaginatedRecordings::fromArray($response);
    }

    /**
     * Get a single recording by ID.
     *
     * @param  string  $id  Recording ID
     * @param  bool  $includeTranscript  Include transcript data
     * @param  bool  $includeSummary  Include summary data
     * @param  bool  $includeActionItems  Include action items
     * @param  string|null  $summarizationId  Filter to a specific summarization ID
     *
     * @throws PocketException
     * @throws Exception
     */
    public function get(
        string $id,
        bool $includeTranscript = true,
        bool $includeSummary = true,
        bool $includeActionItems = true,
        ?string $summarizationId = null
    ): Recording {
        $query = [
            'include_transcript' => $includeTranscript ? 'true' : 'false',
            'include_summarizations' => ($includeSummary || $includeActionItems) ? 'true' : 'false',
            'summarization_id' => $summarizationId,
        ];

        $response = $this->client->get('recordings/'.rawurlencode($id), $query);

        return Recording::fromArray(
            data: $response['data'],
            includeSummary: $includeSummary,
            includeActionItems: $includeActionItems,
            summarizationId: $summarizationId,
        );
    }

    /**
     * Find a recording by ID (alias for get).
     *
     * @param  string  $id  Recording ID
     *
     * @throws PocketException
     */
    public function find(string $id): Recording
    {
        return $this->get($id);
    }

    /**
     * Get all recordings (handles pagination automatically).
     *
     * @param  string|null  $folderId  Filter by folder ID
     * @param  DateTimeInterface|string|null  $startDate  Filter recordings from this date
     * @param  DateTimeInterface|string|null  $endDate  Filter recordings until this date
     * @param  array<string>  $tagIds  Filter by tag IDs
     * @return Generator<int, Recording, mixed, void>
     *
     * @throws PocketException
     */
    public function all(
        ?string $folderId = null,
        DateTimeInterface|string|null $startDate = null,
        DateTimeInterface|string|null $endDate = null,
        array $tagIds = []
    ): Generator {
        $page = 1;
        do {
            $result = $this->list(
                folderId: $folderId,
                startDate: $startDate,
                endDate: $endDate,
                tagIds: $tagIds,
                page: $page,
                limit: 100
            );

            foreach ($result->data as $recording) {
                yield $recording;
            }

            $page++;
        } while ($result->hasMore());
    }

    /**
     * Get recordings for a specific folder.
     *
     * @param  string  $folderId  Folder ID to filter by
     * @param  int  $page  Page number
     * @param  int  $limit  Items per page
     *
     * @throws PocketException
     */
    public function inFolder(string $folderId, int $page = 1, int $limit = 20): PaginatedRecordings
    {
        return $this->list(folderId: $folderId, page: $page, limit: $limit);
    }

    /**
     * Get recordings with specific tags.
     *
     * @param  array<string>  $tagIds  Tag IDs to filter by
     * @param  int  $page  Page number
     * @param  int  $limit  Items per page
     *
     * @throws PocketException
     */
    public function withTags(array $tagIds, int $page = 1, int $limit = 20): PaginatedRecordings
    {
        return $this->list(tagIds: $tagIds, page: $page, limit: $limit);
    }

    /**
     * Get recordings within a date range.
     *
     * @param  DateTimeInterface|string  $startDate  Start date for the range
     * @param  DateTimeInterface|string  $endDate  End date for the range
     * @param  int  $page  Page number
     * @param  int  $limit  Items per page
     *
     * @throws PocketException
     */
    public function betweenDates(
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        int $page = 1,
        int $limit = 20
    ): PaginatedRecordings {
        return $this->list(startDate: $startDate, endDate: $endDate, page: $page, limit: $limit);
    }

    /**
     * Generate a pre-signed S3 URL for uploading a new recording.
     *
     * User API keys require the `recordings:write` scope. Organization API keys
     * are not allowed for this endpoint.
     *
     * @throws PocketException
     * @throws Exception
     */
    public function createUploadUrl(
        ?string $contentType = null,
        ?string $fileName = null,
        int|float|null $duration = null,
        DateTimeInterface|string|null $recordingAt = null,
        ?string $title = null,
    ): UploadUrl {
        $body = array_filter([
            'content_type' => $contentType,
            'file_name' => $fileName,
            'duration' => $duration,
            'recording_at' => $this->formatDateTime($recordingAt),
            'title' => $title,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->post('recordings/upload-url', $body);

        /** @var array<string, mixed> $data */
        $data = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return UploadUrl::fromArray($data);
    }

    /**
     * Create an upload URL and PUT a local file to the signed URL.
     *
     * @throws PocketException
     * @throws GuzzleException
     * @throws Exception
     */
    public function upload(
        string $path,
        ?string $title = null,
        ?string $contentType = null,
        ?string $fileName = null,
        int|float|null $duration = null,
        DateTimeInterface|string|null $recordingAt = null,
    ): UploadUrl {
        if (! is_readable($path) || is_dir($path)) {
            throw new PocketException('Cannot read recording file: '.$path);
        }

        $fileName ??= basename($path);
        $contentType ??= mime_content_type($path) ?: 'application/octet-stream';

        $upload = $this->createUploadUrl(
            contentType: $contentType,
            fileName: $fileName,
            duration: $duration,
            recordingAt: $recordingAt,
            title: $title,
        );

        if ($upload->signedUrl === null) {
            throw new PocketException('Upload URL response did not include a signed URL');
        }

        $headers = $upload->headers;
        $headers['Content-Type'] ??= $contentType;

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new PocketException('Cannot open recording file: '.$path);
        }

        try {
            (new Client(['timeout' => 300]))->put($upload->signedUrl, [
                'body' => $handle,
                'headers' => $headers,
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        return $upload;
    }

    /**
     * Format a date for the API query.
     *
     * @param  DateTimeInterface|string|null  $date  The date to format
     */
    protected function formatDate(DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return $date;
    }

    /**
     * Format a date-time for upload metadata.
     */
    protected function formatDateTime(DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format('c');
        }

        return $date;
    }

    /**
     * Retrieve the raw response data.
     *
     * @return mixed The raw response data returned by the request.
     */
    public function getRawResponse(): mixed
    {
        return $this->rawResponse;
    }
}
