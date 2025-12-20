<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use DateTimeInterface;
use Yannelli\Pocket\Data\PaginatedRecordings;
use Yannelli\Pocket\Data\Recording;
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
            'page' => $page,
            'limit' => min($limit, 100),
        ];

        $response = $this->client->get('recordings', $query);

        return PaginatedRecordings::fromArray($response);
    }

    /**
     * Get a single recording by ID.
     *
     * @param  string  $id  Recording ID
     * @param  bool  $includeTranscript  Include transcript data
     * @param  bool  $includeSummary  Include summary data
     * @param  bool  $includeActionItems  Include action items
     *
     * @throws PocketException
     */
    public function get(
        string $id,
        bool $includeTranscript = true,
        bool $includeSummary = true,
        bool $includeActionItems = true
    ): Recording {
        $query = [
            'include_transcript' => $includeTranscript ? 'true' : 'false',
            'include_summary' => $includeSummary ? 'true' : 'false',
            'include_action_items' => $includeActionItems ? 'true' : 'false',
        ];

        $response = $this->client->get("recordings/{$id}", $query);

        return Recording::fromArray($response['data']);
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
     * @return \Generator<int, Recording, mixed, void>
     *
     * @throws PocketException
     */
    public function all(
        ?string $folderId = null,
        DateTimeInterface|string|null $startDate = null,
        DateTimeInterface|string|null $endDate = null,
        array $tagIds = []
    ): \Generator {
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
}
