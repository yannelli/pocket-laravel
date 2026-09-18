<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Yannelli\Pocket\Enums\RecordingState;

final readonly class Recording implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, Tag>  $tags
     * @param  array<int, ActionItem>  $actionItems
     * @param  array<int, string>  $summarizationsErrors
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?string $folderId,
        public int|string|null $duration,
        public ?RecordingState $state,
        public ?string $language,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $tags = [],
        public ?Transcript $transcript = null,
        public ?Summary $summary = null,
        public ?array $actionItems = [],
        public ?RecordedBy $recordedBy = null,
        public ?DateTimeImmutable $recordingAt = null,
        public ?string $transcriptError = null,
        public ?Translation $translation = null,
        public ?string $translationError = null,
        public array $summarizationsErrors = [],
    ) {}

    /**
     * Create a Recording instance from an array.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public static function fromArray(
        array $data,
        bool $includeSummary = true,
        bool $includeActionItems = true,
        ?string $summarizationId = null,
    ): self {
        $duration = $data['duration'] ?? null;
        $summarizations = is_array($data['summarizations'] ?? null) ? $data['summarizations'] : [];
        $selectedSummarization = null;
        $selectedUpdatedAt = '';

        foreach ($summarizations as $id => $summarization) {
            if (! is_array($summarization) || ! isset($summarization['v2'])) {
                continue;
            }

            if ($summarizationId !== null && ($id === $summarizationId || ($summarization['summarizationId'] ?? null) === $summarizationId)) {
                $selectedSummarization = $summarization;
                break;
            }

            if (($summarization['processingStatus'] ?? null) !== 'completed') {
                continue;
            }

            $updatedAt = (string) ($summarization['updatedAt'] ?? $summarization['updated_at'] ?? '');

            if ($selectedSummarization === null || $updatedAt > $selectedUpdatedAt) {
                $selectedSummarization = $summarization;
                $selectedUpdatedAt = $updatedAt;
            }
        }

        $summary = $includeSummary
            ? ($data['summary'] ?? $selectedSummarization['v2']['summary'] ?? null)
            : null;
        $actionItems = $includeActionItems
            ? ($data['action_items']
                ?? $selectedSummarization['v2']['actionItems']['actionItems']
                ?? $selectedSummarization['v2']['actionItems']['actions']
                ?? $summarizations['v2_action_items']['actionItems']
                ?? [])
            : [];
        $transcript = $data['transcript'] ?? $data['raw_transcript'] ?? null;
        $recordedBy = $data['recorded_by'] ?? null;
        $recordingAt = $data['recording_at'] ?? null;
        $translation = $data['translation'] ?? null;
        $summarizationsErrors = $data['summarizations_errors'] ?? [];

        if (! is_array($actionItems)) {
            $actionItems = [];
        }

        return new self(
            id: $data['id'],
            title: $data['title'] ?? '',
            folderId: $data['folder_id'] ?? null,
            duration: $duration,
            state: RecordingState::tryFrom($data['state'] ?? 'unknown') ?? RecordingState::Unknown,
            language: $data['language'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            tags: isset($data['tags']) && is_array($data['tags']) ? Tag::collection($data['tags']) : [],
            transcript: $transcript !== null ? Transcript::fromArray($transcript) : null,
            summary: $summary !== null ? Summary::fromArray($summary) : null,
            actionItems: ActionItem::collection($actionItems),
            recordedBy: is_array($recordedBy) ? RecordedBy::fromArray($recordedBy) : null,
            recordingAt: $recordingAt !== null ? new DateTimeImmutable((string) $recordingAt) : null,
            transcriptError: $data['transcript_error'] ?? null,
            translation: is_array($translation) ? Translation::fromArray($translation) : null,
            translationError: $data['translation_error'] ?? null,
            summarizationsErrors: is_array($summarizationsErrors)
                ? array_values(array_map(static fn (mixed $error): string => (string) $error, $summarizationsErrors))
                : [],
        );
    }

    /**
     * Create a collection of Recording instances from an array.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, Recording>
     *
     * @throws Exception
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
        if (is_string($this->duration) && ! is_numeric($this->duration)) {
            return $this->duration;
        }

        $duration = (int) ($this->duration ?? 0);
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

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
        return $this->state?->isProcessing() ?? false;
    }

    /**
     * Check if the recording processing failed.
     */
    public function isFailed(): bool
    {
        return $this->state?->isFailed() ?? false;
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
     * @return array<int, ActionItem>
     */
    public function pendingActionItems(): array
    {
        return array_filter($this->actionItems, fn (ActionItem $item) => $item->isPending());
    }

    /**
     * Get completed action items.
     *
     * @return array<int, ActionItem>
     */
    public function completedActionItems(): array
    {
        return array_filter($this->actionItems, fn (ActionItem $item) => $item->isCompleted());
    }

    /**
     * Check if the recording processing is completed.
     */
    public function isCompleted(): bool
    {
        return $this->state?->isCompleted() ?? false;
    }

    /**
     * Convert the recording to JSON-serializable array.
     *
     * @return array{
     *     id: string,
     *     title: string,
     *     folder_id: string|null,
     *     duration?: int|string|null,
     *     state?: string|null,
     *     language: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     tags: array<int, array{id: string, name: string, color: string, usage_count?: int}>,
     *     transcript?: array{text: string, segments: array<int, array{start: float, end: float, text: string, speaker?: string}>},
     *     summary?: array{title: string, sections: array<int, array{heading: string, content: string}>},
     *     action_items?: array<int, array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the recording to an array.
     *
     * @return array{
     *     id: string,
     *     title: string,
     *     folder_id: string|null,
     *     duration?: int|string|null,
     *     state?: string|null,
     *     language: string|null,
     *     created_at: string,
     *     updated_at: string,
     *     tags: array<int, array{id: string, name: string, color: string, usage_count?: int}>,
     *     transcript?: array{text: string, segments: array<int, array{start: float, end: float, text: string, speaker?: string}>},
     *     summary?: array{title: string, sections: array<int, array{heading: string, content: string}>},
     *     action_items?: array<int, array{id: string, title: string, description?: string, status: string, priority: string, due_date?: string}>
     * }
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'folder_id' => $this->folderId,
            'duration' => $this->duration,
            'state' => $this->state?->value,
            'language' => $this->language,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'tags' => array_map(fn (Tag $t) => $t->toArray(), $this->tags),
        ];

        if ($this->recordedBy !== null) {
            $data['recorded_by'] = $this->recordedBy->toArray();
        }

        if ($this->recordingAt !== null) {
            $data['recording_at'] = $this->recordingAt->format('c');
        }

        if ($this->transcript !== null) {
            $data['transcript'] = $this->transcript->toArray();
        }

        if ($this->transcriptError !== null) {
            $data['transcript_error'] = $this->transcriptError;
        }

        if ($this->summary !== null) {
            $data['summary'] = $this->summary->toArray();
        }

        if (count($this->actionItems) > 0) {
            $data['action_items'] = array_map(fn (ActionItem $i) => $i->toArray(), $this->actionItems);
        }

        if ($this->translation !== null) {
            $data['translation'] = $this->translation->toArray();
        }

        if ($this->translationError !== null) {
            $data['translation_error'] = $this->translationError;
        }

        if ($this->summarizationsErrors !== []) {
            $data['summarizations_errors'] = $this->summarizationsErrors;
        }

        return $data;
    }
}
