<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class TranscriptSegment implements Arrayable, JsonSerializable
{
    public function __construct(
        public float $start,
        public float $end,
        public string $text,
        public ?string $speaker = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            start: (float) $data['start'],
            end: (float) $data['end'],
            text: $data['text'],
            speaker: $data['speaker'] ?? null,
        );
    }

    /**
     * @param  array<array>  $items
     * @return array<TranscriptSegment>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    public function duration(): float
    {
        return $this->end - $this->start;
    }

    public function toArray(): array
    {
        return array_filter([
            'start' => $this->start,
            'end' => $this->end,
            'text' => $this->text,
            'speaker' => $this->speaker,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
