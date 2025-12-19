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
        public string $color,
        public ?int $usageCount = null,
    ) {}

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
     * @param  array<array>  $items
     * @return array<Tag>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'usage_count' => $this->usageCount,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
