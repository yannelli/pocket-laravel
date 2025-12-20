<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Yannelli\Pocket\Enums\ActionItemPriority;
use Yannelli\Pocket\Enums\ActionItemStatus;

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

    /**
     * Create an ActionItem instance from an array.
     *
     * @param  array{id: string, title: string, description?: string|null, status?: string, priority?: string, due_date?: string|null}  $data
     */
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
     * Create a collection of ActionItem instances from an array.
     *
     * @param  array<array{id: string, title: string, description?: string|null, status?: string, priority?: string, due_date?: string|null}>  $items
     * @return array<ActionItem>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Check if the action item is pending.
     */
    public function isPending(): bool
    {
        return $this->status === ActionItemStatus::Pending;
    }

    /**
     * Check if the action item is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ActionItemStatus::Completed;
    }

    /**
     * Check if the action item is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->dueDate === null || $this->isCompleted()) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable('today');
    }

    /**
     * Convert to array representation.
     *
     * @return array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}
     */
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

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
