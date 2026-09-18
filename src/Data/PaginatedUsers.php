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
 * @implements IteratorAggregate<int, OrganizationUser>
 * @implements Arrayable<string, mixed>
 */
final readonly class PaginatedUsers implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  array<int, OrganizationUser>  $data
     */
    public function __construct(
        public array $data,
        public Pagination $pagination,
    ) {}

    /**
     * @param  array<string, mixed>  $response
     *
     * @throws Exception
     */
    public static function fromArray(array $response): self
    {
        $items = $response['data'] ?? [];
        $users = [];

        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $users[] = OrganizationUser::fromArray($item);
                }
            }
        }

        return new self(
            data: $users,
            pagination: Pagination::fromArray($response['pagination']),
        );
    }

    /**
     * @return array<int, OrganizationUser>
     */
    public function items(): array
    {
        return $this->data;
    }

    public function first(): ?OrganizationUser
    {
        return $this->data[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
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

    public function nextPage(): ?int
    {
        return $this->pagination->nextPage();
    }

    /**
     * @return ArrayIterator<int, OrganizationUser>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array{page: int, limit: int, total: int, total_pages: int, has_more: bool}}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(fn (OrganizationUser $user) => $user->toArray(), $this->data),
            'pagination' => $this->pagination->toArray(),
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array{page: int, limit: int, total: int, total_pages: int, has_more: bool}}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
