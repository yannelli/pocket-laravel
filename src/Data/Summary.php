<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Summary implements Arrayable, JsonSerializable
{
    /**
     * @param  array<SummarySection>  $sections
     * @param  array<string>  $bulletPoints
     */
    public function __construct(
        public string $title,
        public array $sections = [],
        public ?string $markdown = null,
        public array $bulletPoints = [],
        public ?string $emoji = null,
    ) {}

    /**
     * Create a Summary instance from an array.
     *
     * @param  string|array{title?: string, sections?: array<array{heading: string, content: string}>, markdown?: string, summary?: string, bulletPoints?: array<string>, bullet_points?: array<string>, emoji?: string}  $data
     */
    public static function fromArray(string|array $data): self
    {
        if (is_string($data)) {
            return new self(title: '', markdown: $data);
        }

        return new self(
            title: $data['title'] ?? '',
            sections: isset($data['sections'])
                ? SummarySection::collection($data['sections'])
                : [],
            markdown: $data['markdown'] ?? $data['summary'] ?? null,
            bulletPoints: $data['bulletPoints'] ?? $data['bullet_points'] ?? [],
            emoji: $data['emoji'] ?? null,
        );
    }

    /**
     * Find a section by heading.
     *
     * @param  string  $heading  The heading to search for
     */
    public function findSection(string $heading): ?SummarySection
    {
        foreach ($this->sections as $section) {
            if ($section->heading === $heading) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'title' => $this->title,
            'sections' => array_map(fn (SummarySection $s) => $s->toArray(), $this->sections),
        ];

        if ($this->markdown !== null) {
            $data['markdown'] = $this->markdown;
        }

        if ($this->bulletPoints !== []) {
            $data['bulletPoints'] = $this->bulletPoints;
        }

        if ($this->emoji !== null) {
            $data['emoji'] = $this->emoji;
        }

        return $data;
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
