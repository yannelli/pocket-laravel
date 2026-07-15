<?php

use Yannelli\Pocket\Data\PaginatedRecordings;

function paginatedRecordingResponse(array $data, bool $hasMore = false): array
{
    return [
        'data' => $data,
        'pagination' => [
            'page' => 2,
            'limit' => 20,
            'total' => count($data),
            'total_pages' => 2,
            'has_more' => $hasMore,
        ],
    ];
}

it('exposes pagination collection helpers and serialization', function () {
    $recordings = PaginatedRecordings::fromArray(paginatedRecordingResponse([
        [
            'id' => 'rec_1',
            'title' => 'First',
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-01-01T00:00:00Z',
        ],
        [
            'id' => 'rec_2',
            'title' => 'Last',
            'created_at' => '2026-01-02T00:00:00Z',
            'updated_at' => '2026-01-02T00:00:00Z',
        ],
    ], true));

    expect($recordings->items())->toHaveCount(2)
        ->and($recordings->first()->id)->toBe('rec_1')
        ->and($recordings->last()->id)->toBe('rec_2')
        ->and($recordings->isEmpty())->toBeFalse()
        ->and($recordings->isNotEmpty())->toBeTrue()
        ->and($recordings->currentPage())->toBe(2)
        ->and($recordings->nextPage())->toBe(3)
        ->and($recordings->previousPage())->toBe(1)
        ->and(iterator_to_array($recordings))->toHaveCount(2)
        ->and($recordings->toArray()['data'])->toHaveCount(2)
        ->and(json_decode(json_encode($recordings), true))->toBe($recordings->toArray());
});

it('handles empty recording pages', function () {
    $recordings = PaginatedRecordings::fromArray(paginatedRecordingResponse([]));

    expect($recordings->first())->toBeNull()
        ->and($recordings->last())->toBeNull()
        ->and($recordings->isEmpty())->toBeTrue()
        ->and($recordings->hasMore())->toBeFalse()
        ->and($recordings->nextPage())->toBeNull();
});
