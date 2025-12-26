<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class TranscriptSegment implements Arrayable, JsonSerializable
{
    public function __construct(
        public float $start,
        public float $end,
        public ?string $text,
        public ?string $speaker = null,
    ) {}

    /**
     * Create a TranscriptSegment instance from an array.
     *
     * @param  array{start: float|int|string, end: float|int|string, text?: string, speaker?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            start: (float) $data['start'],
            end: (float) $data['end'],
            text: $data['text'] ?? '',
            speaker: $data['speaker'] ?? null,
        );
    }

    /**
     * Create a collection of TranscriptSegment instances from an array.
     *
     * @param  array<array{start: float|int|string, end: float|int|string, text: string, speaker?: string|null}>  $items
     * @return array<TranscriptSegment>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Get the duration of this segment in seconds.
     */
    public function duration(): float
    {
        return $this->end - $this->start;
    }

    /**
     * Convert to array representation.
     *
     * @return array{start: float, end: float, text: string, speaker?: string}
     */
    public function toArray(): array
    {
        return array_filter([
            'start' => $this->start,
            'end' => $this->end,
            'text' => $this->text,
            'speaker' => $this->speaker,
        ], fn ($value) => $value !== null);
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{start: float, end: float, text: string, speaker?: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
