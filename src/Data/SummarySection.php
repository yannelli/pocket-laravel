<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

final readonly class SummarySection implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $heading,
        public string $content,
    ) {}

    /**
     * Create a SummarySection instance from an array.
     *
     * @param  array{heading: string, content: string}  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            heading: $data['heading'],
            content: $data['content'],
        );
    }

    /**
     * Create a collection of SummarySection instances from an array.
     *
     * @param  array<array{heading: string, content: string}>  $items
     * @return array<SummarySection>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    /**
     * Convert to array representation.
     *
     * @return array{heading: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'content' => $this->content,
        ];
    }

    /**
     * Convert to JSON-serializable array.
     *
     * @return array{heading: string, content: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
