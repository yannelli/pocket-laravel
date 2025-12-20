<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Pagination implements Arrayable, JsonSerializable
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
        public bool $hasMore,
    ) {}

    /**
     * Create a Pagination instance from an array.
     *
     * @param  array{page: int|string, limit: int|string, total: int|string, total_pages: int|string, has_more: bool}  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            page: (int) $data['page'],
            limit: (int) $data['limit'],
            total: (int) $data['total'],
            totalPages: (int) $data['total_pages'],
            hasMore: (bool) $data['has_more'],
        );
    }

    /**
     * Check if this is the first page.
     *
     * @return bool
     */
    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    /**
     * Check if this is the last page.
     *
     * @return bool
     */
    public function isLastPage(): bool
    {
        return ! $this->hasMore;
    }

    /**
     * Get the next page number, or null if on the last page.
     *
     * @return int|null
     */
    public function nextPage(): ?int
    {
        return $this->hasMore ? $this->page + 1 : null;
    }

    /**
     * Get the previous page number, or null if on the first page.
     *
     * @return int|null
     */
    public function previousPage(): ?int
    {
        return $this->page > 1 ? $this->page - 1 : null;
    }

    /**
     * Convert to array representation.
     *
     * @return array{page: int, limit: int, total: int, total_pages: int, has_more: bool}
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'total_pages' => $this->totalPages,
            'has_more' => $this->hasMore,
        ];
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{page: int, limit: int, total: int, total_pages: int, has_more: bool}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
