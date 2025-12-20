<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Yannelli\Pocket\Enums\RecordingState;

final readonly class Recording implements Arrayable, JsonSerializable
{
    /**
     * @param  array<Tag>  $tags
     * @param  array<ActionItem>  $actionItems
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $folderId,
        public int $duration,
        public RecordingState $state,
        public ?string $language,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $tags = [],
        public ?Transcript $transcript = null,
        public ?Summary $summary = null,
        public array $actionItems = [],
    ) {}

    /**
     * Create a Recording instance from an array.
     *
     * @param  array{id: string, title: string, folder_id?: string|null, duration: int|string, state?: string, language?: string|null, created_at: string, updated_at: string, tags?: array<array{id: string, name: string, color: string, usage_count?: int|null}>, transcript?: array{text: string, segments?: array<array{start: float|int|string, end: float|int|string, text: string, speaker?: string|null}>}, summary?: array{title: string, sections?: array<array{heading: string, content: string}>}, action_items?: array<array{id: string, title: string, description?: string|null, status?: string, priority?: string, due_date?: string|null}>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            folderId: $data['folder_id'] ?? null,
            duration: (int) $data['duration'],
            state: RecordingState::tryFrom($data['state'] ?? 'unknown') ?? RecordingState::Unknown,
            language: $data['language'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            tags: isset($data['tags']) ? Tag::collection($data['tags']) : [],
            transcript: isset($data['transcript']) ? Transcript::fromArray($data['transcript']) : null,
            summary: isset($data['summary']) ? Summary::fromArray($data['summary']) : null,
            actionItems: isset($data['action_items']) ? ActionItem::collection($data['action_items']) : [],
        );
    }

    /**
     * Create a collection of Recording instances from an array.
     *
     * @param  array<array{id: string, title: string, folder_id?: string|null, duration: int|string, state?: string, language?: string|null, created_at: string, updated_at: string, tags?: array<array{id: string, name: string, color: string, usage_count?: int|null}>, transcript?: array{text: string, segments?: array<array{start: float|int|string, end: float|int|string, text: string, speaker?: string|null}>}, summary?: array{title: string, sections?: array<array{heading: string, content: string}>}, action_items?: array<array{id: string, title: string, description?: string|null, status?: string, priority?: string, due_date?: string|null}>}>  $items
     * @return array<Recording>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Get the duration in a human-readable format.
     */
    public function formattedDuration(): string
    {
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Check if the recording is currently processing.
     */
    public function isProcessing(): bool
    {
        return $this->state->isProcessing();
    }

    /**
     * Check if the recording processing is completed.
     */
    public function isCompleted(): bool
    {
        return $this->state->isCompleted();
    }

    /**
     * Check if the recording processing failed.
     */
    public function isFailed(): bool
    {
        return $this->state->isFailed();
    }

    /**
     * Check if the recording has a transcript.
     */
    public function hasTranscript(): bool
    {
        return $this->transcript !== null;
    }

    /**
     * Check if the recording has a summary.
     */
    public function hasSummary(): bool
    {
        return $this->summary !== null;
    }

    /**
     * Check if the recording has action items.
     */
    public function hasActionItems(): bool
    {
        return count($this->actionItems) > 0;
    }

    /**
     * Get pending action items.
     *
     * @return array<ActionItem>
     */
    public function pendingActionItems(): array
    {
        return array_filter($this->actionItems, fn (ActionItem $item) => $item->isPending());
    }

    /**
     * Get completed action items.
     *
     * @return array<ActionItem>
     */
    public function completedActionItems(): array
    {
        return array_filter($this->actionItems, fn (ActionItem $item) => $item->isCompleted());
    }

    /**
     * Convert the recording to an array.
     *
     * @return array{id: string, title: string, folder_id: string|null, duration: int, state: string, language: string|null, created_at: string, updated_at: string, tags: array<array{id: string, name: string, color: string, usage_count?: int}>, transcript?: array{text: string, segments: array<array{start: float, end: float, text: string, speaker?: string}>}, summary?: array{title: string, sections: array<array{heading: string, content: string}>}, action_items?: array<array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}>}
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'folder_id' => $this->folderId,
            'duration' => $this->duration,
            'state' => $this->state->value,
            'language' => $this->language,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'tags' => array_map(fn (Tag $t) => $t->toArray(), $this->tags),
        ];

        if ($this->transcript !== null) {
            $data['transcript'] = $this->transcript->toArray();
        }

        if ($this->summary !== null) {
            $data['summary'] = $this->summary->toArray();
        }

        if (count($this->actionItems) > 0) {
            $data['action_items'] = array_map(fn (ActionItem $i) => $i->toArray(), $this->actionItems);
        }

        return $data;
    }

    /**
     * Convert the recording to JSON-serializable array.
     *
     * @return array{id: string, title: string, folder_id: string|null, duration: int, state: string, language: string|null, created_at: string, updated_at: string, tags: array<array{id: string, name: string, color: string, usage_count?: int}>, transcript?: array{text: string, segments: array<array{start: float, end: float, text: string, speaker?: string}>}, summary?: array{title: string, sections: array<array{heading: string, content: string}>}, action_items?: array<array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
