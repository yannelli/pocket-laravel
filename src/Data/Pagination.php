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

    public function isFirstPage(): bool
    {
        return $this->page === 1;
    }

    public function isLastPage(): bool
    {
        return ! $this->hasMore;
    }

    public function nextPage(): ?int
    {
        return $this->hasMore ? $this->page + 1 : null;
    }

    public function previousPage(): ?int
    {
        return $this->page > 1 ? $this->page - 1 : null;
    }

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

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
