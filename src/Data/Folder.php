<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Folder implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $isDefault,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            isDefault: $data['is_default'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }

    /**
     * @param  array<array>  $items
     * @return array<Folder>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
