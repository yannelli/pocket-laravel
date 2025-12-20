<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Tag implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $color,
        public ?int $usageCount = null,
    ) {}

    /**
     * Create a Tag instance from an array.
     *
     * @param  array{id: string, name: string, color?: string, usage_count?: int|null}  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            color: $data['color'],
            usageCount: $data['usage_count'] ?? null,
        );
    }

    /**
     * Create a collection of Tag instances from an array.
     *
     * @param  array<array{id: string, name: string, color?: string, usage_count?: int|null}>  $items
     * @return array<Tag>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Convert to array representation.
     *
     * @return array{id: string, name: string, color: string, usage_count?: int}
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'usage_count' => $this->usageCount,
        ], fn ($value) => $value !== null);
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{id: string, name: string, color: string, usage_count?: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
