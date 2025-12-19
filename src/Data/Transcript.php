<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Transcript implements Arrayable, JsonSerializable
{
    /**
     * @param  array<TranscriptSegment>  $segments
     */
    public function __construct(
        public string $text,
        public array $segments = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            text: $data['text'],
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

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'segments' => array_map(fn (TranscriptSegment $s) => $s->toArray(), $this->segments),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
