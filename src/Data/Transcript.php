<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Transcript implements Arrayable, JsonSerializable
{
    /**
     * @param  array<TranscriptSegment>  $segments
     */
    public function __construct(
        public ?string $text = null,
        public ?array $segments = [],
    ) {}

    /**
     * Create a Transcript instance from an array.
     *
     * @param  array{text: string, segments?: array<array{start: float|int|string|null, end: float|int|string|null, text?: string, speaker?: string|null}>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'] ?? null,
            segments: isset($data['segments'])
                ? TranscriptSegment::collection($data['segments'])
                : [],
        );
    }

    /**
     * Get all unique speakers from the transcript segments.
     *
     * @return array<string>
     */
    public function speakers(): array
    {
        $speakers = [];
        foreach ($this->segments as $segment) {
            if ($segment->speaker !== null && ! in_array($segment->speaker, $speakers, true)) {
                $speakers[] = $segment->speaker;
            }
        }

        return $speakers;
    }

    /**
     * Get segments for a specific speaker.
     *
     * @return array<TranscriptSegment>
     */
    public function segmentsForSpeaker(string $speaker): array
    {
        return array_filter(
            $this->segments,
            fn (TranscriptSegment $segment) => $segment->speaker === $speaker
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array{text: string, segments: array<array{start?: float, end?: float, text: string, speaker?: string}>}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'segments' => array_map(fn (TranscriptSegment $s) => $s->toArray(), $this->segments),
        ];
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{text: string, segments: array<array{start: float, end: float, text: string, speaker?: string}>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
