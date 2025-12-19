<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Data;

use ArrayIterator;
use Countable;
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

    public function first(): ?Recording
    {
        return $this->data[0] ?? null;
    }

    public function last(): ?Recording
    {
        $count = count($this->data);

        return $count > 0 ? $this->data[$count - 1] : null;
    }

    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function total(): int
    {
        return $this->pagination->total;
    }

    public function hasMore(): bool
    {
        return $this->pagination->hasMore;
    }

    public function currentPage(): int
    {
        return $this->pagination->page;
    }

    public function nextPage(): ?int
    {
        return $this->pagination->nextPage();
    }

    public function previousPage(): ?int
    {
        return $this->pagination->previousPage();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn (Recording $r) => $r->toArray(), $this->data),
            'pagination' => $this->pagination->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
