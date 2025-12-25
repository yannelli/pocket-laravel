<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use ArrayIterator;
use Countable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<int, Recording>
 */
final readonly class PaginatedRecordings implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  array<Recording>  $data
     */
    public function __construct(
        public array $data,
        public Pagination $pagination,
    ) {}

    /**
     * Create a PaginatedRecordings instance from an API response array.
     *
     * @param  array{data: array<array{id: string, title: string, folder_id?: string|null, duration: int|string, state?: string, language?: string|null, created_at: string, updated_at: string, tags?: array<array{id: string, name: string, color: string, usage_count?: int|null}>}>, pagination: array{page: int|string, limit: int|string, total: int|string, total_pages: int|string, has_more: bool}}  $response
     * @throws Exception
     */
    public static function fromArray(array $response): self
    {
        return new self(
            data: Recording::collection($response['data']),
            pagination: Pagination::fromArray($response['pagination']),
        );
    }

    /**
     * @return array<Recording>
     */
    public function items(): array
    {
        return $this->data;
    }

    /**
     * Get the first recording in the collection.
     */
    public function first(): ?Recording
    {
        return $this->data[0] ?? null;
    }

    /**
     * Get the last recording in the collection.
     */
    public function last(): ?Recording
    {
        $count = count($this->data);

        return $count > 0 ? $this->data[$count - 1] : null;
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    /**
     * Check if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the count of recordings in this page.
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get the total number of recordings across all pages.
     */
    public function total(): int
    {
        return $this->pagination->total;
    }

    /**
     * Check if there are more pages available.
     */
    public function hasMore(): bool
    {
        return $this->pagination->hasMore;
    }

    /**
     * Get the current page number.
     */
    public function currentPage(): int
    {
        return $this->pagination->page;
    }

    /**
     * Get the next page number, or null if on the last page.
     */
    public function nextPage(): ?int
    {
        return $this->pagination->nextPage();
    }

    /**
     * Get the previous page number, or null if on the first page.
     */
    public function previousPage(): ?int
    {
        return $this->pagination->previousPage();
    }

    /**
     * Get an iterator for the recordings.
     *
     * @return ArrayIterator<int, Recording>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Convert to array representation.
     *
     * @return array{data: array<array{id: string, title: string, folder_id: string|null, duration: int, state: string, language: string|null, created_at: string, updated_at: string, tags: array<array{id: string, name: string, color: string, usage_count?: int}>}>, pagination: array{page: int, limit: int, total: int, total_pages: int, has_more: bool}}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Recording $r) => $r->toArray(), $this->data),
            'pagination' => $this->pagination->toArray(),
        ];
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{data: array<array{id: string, title: string, folder_id: string|null, duration: int, state: string, language: string|null, created_at: string, updated_at: string, tags: array<array{id: string, name: string, color: string, usage_count?: int}>}>, pagination: array{page: int, limit: int, total: int, total_pages: int, has_more: bool}}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
