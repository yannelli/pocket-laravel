<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use DateTimeInterface;
use Yannelli\Pocket\Data\SearchResults;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class SearchResource
{
    /**
     * Create a new SearchResource instance.
     */
    public function __construct(
        protected PocketClient $client
    ) {}

    /**
     * Search across recording transcripts, summaries, and action items.
     *
     * @param  array<int, string>  $folderIds
     * @param  array<int, string>  $recordingIds
     *
     * @throws PocketException
     */
    public function query(
        string $query,
        int $limit = 8,
        DateTimeInterface|string|null $dateFrom = null,
        DateTimeInterface|string|null $dateTo = null,
        array $folderIds = [],
        array $recordingIds = [],
    ): SearchResults {
        $body = [
            'query' => $query,
            'limit' => max(1, min($limit, 20)),
        ];

        $filters = array_filter([
            'dateFrom' => $this->formatDate($dateFrom),
            'dateTo' => $this->formatDate($dateTo),
            'folderIds' => $folderIds !== [] ? array_values($folderIds) : null,
            'recordingIds' => $recordingIds !== [] ? array_values($recordingIds) : null,
        ], static fn (mixed $value): bool => $value !== null);

        if ($filters !== []) {
            $body['filters'] = $filters;
        }

        $response = $this->client->post('search', $body);

        return SearchResults::fromArray($response, $query);
    }

    /**
     * Format a date for the search filters.
     */
    protected function formatDate(DateTimeInterface|string|null $date): ?string
    {
        if ($date === null) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format('c');
        }

        return $date;
    }
}
