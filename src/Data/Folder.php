<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Folder implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, Folder>  $children
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $isDefault,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $color = null,
        public ?string $kind = null,
        public ?string $parentFolderId = null,
        public ?string $spaceId = null,
        public ?int $recordingCount = null,
        public ?int $totalRecordingCount = null,
        public array $children = [],
    ) {}

    /**
     * Create a Folder instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(array $data): self
    {
        $children = $data['children'] ?? [];

        return new self(
            id: $data['id'],
            name: $data['name'] ?? '',
            isDefault: (bool) ($data['is_default'] ?? false),
            createdAt: new DateTimeImmutable((string) ($data['created_at'] ?? 'now')),
            updatedAt: new DateTimeImmutable((string) ($data['updated_at'] ?? 'now')),
            color: $data['color'] ?? null,
            kind: $data['kind'] ?? null,
            parentFolderId: $data['parent_folder_id'] ?? null,
            spaceId: $data['space_id'] ?? null,
            recordingCount: isset($data['recording_count']) ? (int) $data['recording_count'] : null,
            totalRecordingCount: isset($data['total_recording_count']) ? (int) $data['total_recording_count'] : null,
            children: is_array($children) ? self::collection($children) : [],
        );
    }

    /**
     * Create a collection of Folder instances from an array.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, Folder>
     *
     * @throws Exception
     */
    public static function collection(array $items): array
    {
        return array_values(array_map(fn (array $item) => self::fromArray($item), $items));
    }

    /**
     * Flatten this folder and its descendants into a single list.
     *
     * @return array<int, Folder>
     */
    public function flatten(): array
    {
        $folders = [$this];

        foreach ($this->children as $child) {
            $folders = array_merge($folders, $child->flatten());
        }

        return $folders;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'is_default' => $this->isDefault,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'color' => $this->color,
            'kind' => $this->kind,
            'parent_folder_id' => $this->parentFolderId,
            'space_id' => $this->spaceId,
            'recording_count' => $this->recordingCount,
            'total_recording_count' => $this->totalRecordingCount,
            'children' => $this->children !== []
                ? array_map(fn (Folder $folder) => $folder->toArray(), $this->children)
                : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
