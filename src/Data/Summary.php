<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class Summary implements Arrayable, JsonSerializable
{
    /**
     * @param array<SummarySection> $sections
     */
    public function __construct(
        public string $title,
        public array $sections = [],
    ) {}

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

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'sections' => array_map(fn (SummarySection $s) => $s->toArray(), $this->sections),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
