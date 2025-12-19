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

    public static function fromArray(array $data): self
    {
        return new self(
            heading: $data['heading'],
            content: $data['content'],
        );
    }

    /**
     * @param  array<array>  $items
     * @return array<SummarySection>
     */
    public static function collection(array $items): array
    {
        return array_map(fn (array $item) => self::fromArray($item), $items);
    }

    public function toArray(): array
    {
        return [
            'heading' => $this->heading,
            'content' => $this->content,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
