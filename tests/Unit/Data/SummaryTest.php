<?php

use Yannelli\Pocket\Data\Summary;

it('normalizes and serializes current summaries', function () {
    $summary = Summary::fromArray([
        'title' => 'Standup',
        'markdown' => 'Summary text',
        'bulletPoints' => ['One', 'Two'],
        'emoji' => 'notes',
    ]);

    expect($summary->toArray())->toBe([
        'title' => 'Standup',
        'sections' => [],
        'markdown' => 'Summary text',
        'bulletPoints' => ['One', 'Two'],
        'emoji' => 'notes',
    ])->and(json_decode(json_encode($summary), true))->toBe($summary->toArray());
});

it('supports legacy sections and string summaries', function () {
    $legacy = Summary::fromArray([
        'title' => 'Legacy',
        'sections' => [
            ['heading' => 'Decisions', 'content' => 'Ship it'],
        ],
    ]);
    $string = Summary::fromArray('Plain summary');

    expect($legacy->findSection('Decisions')->content)->toBe('Ship it')
        ->and($legacy->findSection('Missing'))->toBeNull()
        ->and($string->markdown)->toBe('Plain summary');
});
