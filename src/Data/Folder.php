<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Exception;
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

    /**
     * Create a Folder instance from an array.
     *
     * @param  array{id: string, name: string, is_default: bool, created_at: string, updated_at: string}  $data
     *
     * @throws Exception
     */
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
     * Create a collection of Folder instances from an array.
     *
     * @param  array<array{id: string, name: string, is_default: bool, created_at: string, updated_at: string}>  $items
     * @return array<Folder>
     *
     * @throws Exception
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Convert to array representation.
     *
     * @return array{id: string, name: string, is_default: bool, created_at: string, updated_at: string}
     */
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

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{id: string, name: string, is_default: bool, created_at: string, updated_at: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
