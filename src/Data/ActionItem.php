<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Data;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use PocketLabs\Pocket\Enums\ActionItemPriority;
use PocketLabs\Pocket\Enums\ActionItemStatus;

final readonly class ActionItem implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description = null,
        public ActionItemStatus $status = ActionItemStatus::Pending,
        public ActionItemPriority $priority = ActionItemPriority::Medium,
        public ?DateTimeImmutable $dueDate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            status: ActionItemStatus::tryFrom($data['status'] ?? 'pending') ?? ActionItemStatus::Pending,
            priority: ActionItemPriority::tryFrom($data['priority'] ?? 'medium') ?? ActionItemPriority::Medium,
            dueDate: isset($data['due_date']) ? new DateTimeImmutable($data['due_date']) : null,
        );
    }

    /**
     * @param array<array> $items
     * @return array<ActionItem>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    public function isPending(): bool
    {
        return $this->status === ActionItemStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === ActionItemStatus::Completed;
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->isCompleted()) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable('today');
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_date' => $this->dueDate?->format('Y-m-d'),
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
