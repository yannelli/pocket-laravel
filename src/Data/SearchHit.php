<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class SearchHit implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public ?string $recordingId = null,
        public ?string $title = null,
        public ?string $snippet = null,
        public ?string $type = null,
        public int|float|null $score = null,
        public array $raw = [],
    ) {}

    /**
     * Create a SearchHit instance from an array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $snippet = $data['snippet'] ?? $data['text'] ?? $data['content'] ?? $data['excerpt'] ?? null;

        return new self(
            recordingId: isset($data['recording_id']) || isset($data['recordingId']) || isset($data['id'])
                ? (string) ($data['recording_id'] ?? $data['recordingId'] ?? $data['id'])
                : null,
            title: $data['title'] ?? $data['recording_title'] ?? $data['recordingTitle'] ?? null,
            snippet: is_scalar($snippet) ? (string) $snippet : null,
            type: $data['type'] ?? $data['source'] ?? $data['kind'] ?? null,
            score: isset($data['score']) && is_numeric($data['score'])
                ? $data['score'] + 0
                : (isset($data['relevance']) && is_numeric($data['relevance']) ? $data['relevance'] + 0 : null),
            raw: $data,
        );
    }

    /**
     * Create a collection of SearchHit instances from an array.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, SearchHit>
     */
    public static function collection(array $items): array
    {
        return array_values(array_map(fn (array $item) => self::fromArray($item), $items));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'recording_id' => $this->recordingId,
            'title' => $this->title,
            'snippet' => $this->snippet,
            'type' => $this->type,
            'score' => $this->score,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
