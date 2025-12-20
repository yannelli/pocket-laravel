<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Summary implements Arrayable, JsonSerializable
{
    /**
     * @param  array<SummarySection>  $sections
     */
    public function __construct(
        public string $title,
        public array $sections = [],
    ) {}

    /**
     * Create a Summary instance from an array.
     *
     * @param  array{title: string, sections?: array<array{heading: string, content: string}>}  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            sections: isset($data['sections'])
                ? SummarySection::collection($data['sections'])
                : [],
        );
    }

    /**
     * Find a section by heading.
     *
     * @param  string  $heading  The heading to search for
     * @return SummarySection|null
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
     * @return array{title: string, sections: array<array{heading: string, content: string}>}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'sections' => array_map(fn (SummarySection $s) => $s->toArray(), $this->sections),
        ];
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{title: string, sections: array<array{heading: string, content: string}>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
