<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements IteratorAggregate<int, SearchHit>
 * @implements Arrayable<string, mixed>
 */
final readonly class SearchResults implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  array<int, SearchHit>  $data
     */
    public function __construct(
        public array $data,
        public ?string $query = null,
    ) {}

    /**
     * Create SearchResults from an API response array.
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromArray(array $response, ?string $query = null): self
    {
        $items = $response['data'] ?? [];

        if (! is_array($items) || $items === []) {
            $items = [];
        } elseif (! array_is_list($items)) {
            $items = isset($items['id']) || isset($items['recording_id']) || isset($items['recordingId'])
                ? [$items]
                : array_values(array_filter($items, 'is_array'));
        }

        /** @var array<int, array<string, mixed>> $items */
        return new self(
            data: SearchHit::collection($items),
            query: $query ?? (isset($response['query']) ? (string) $response['query'] : null),
        );
    }

    /**
     * @return array<int, SearchHit>
     */
    public function items(): array
    {
        return $this->data;
    }

    public function first(): ?SearchHit
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

    /**
     * @return ArrayIterator<int, SearchHit>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return array{query: string|null, data: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'data' => array_map(fn (SearchHit $hit) => $hit->toArray(), $this->data),
        ];
    }

    /**
     * @return array{query: string|null, data: array<int, array<string, mixed>>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
