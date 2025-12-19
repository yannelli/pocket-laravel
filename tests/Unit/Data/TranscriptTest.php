<?php

use PocketLabs\Pocket\Data\Transcript;
use PocketLabs\Pocket\Data\TranscriptSegment;

it('can create a transcript from array', function () {
    $transcript = Transcript::fromArray([
        'text' => 'Full transcript text...',
        'segments' => [
            ['start' => 0, 'end' => 5.5, 'text' => 'Hello everyone', 'speaker' => 'Speaker 1'],
            ['start' => 5.5, 'end' => 10.0, 'text' => 'Welcome to the meeting', 'speaker' => 'Speaker 2'],
        ],
    ]);

    expect($transcript->text)->toBe('Full transcript text...')
        ->and($transcript->segments)->toHaveCount(2)
        ->and($transcript->segments[0])->toBeInstanceOf(TranscriptSegment::class);
});

it('can get unique speakers', function () {
    $transcript = Transcript::fromArray([
        'text' => 'Transcript text',
        'segments' => [
            ['start' => 0, 'end' => 5, 'text' => 'Hello', 'speaker' => 'Alice'],
            ['start' => 5, 'end' => 10, 'text' => 'Hi', 'speaker' => 'Bob'],
            ['start' => 10, 'end' => 15, 'text' => 'How are you?', 'speaker' => 'Alice'],
        ],
    ]);

    expect($transcript->speakers())->toBe(['Alice', 'Bob']);
});

it('can get segments for a specific speaker', function () {
    $transcript = Transcript::fromArray([
        'text' => 'Transcript text',
        'segments' => [
            ['start' => 0, 'end' => 5, 'text' => 'Hello', 'speaker' => 'Alice'],
            ['start' => 5, 'end' => 10, 'text' => 'Hi', 'speaker' => 'Bob'],
            ['start' => 10, 'end' => 15, 'text' => 'How are you?', 'speaker' => 'Alice'],
        ],
    ]);

    $aliceSegments = $transcript->segmentsForSpeaker('Alice');

    expect($aliceSegments)->toHaveCount(2);
});

it('can create a transcript segment', function () {
    $segment = TranscriptSegment::fromArray([
        'start' => 0,
        'end' => 5.5,
        'text' => 'Hello everyone',
        'speaker' => 'Speaker 1',
    ]);

    expect($segment->start)->toBe(0.0)
        ->and($segment->end)->toBe(5.5)
        ->and($segment->text)->toBe('Hello everyone')
        ->and($segment->speaker)->toBe('Speaker 1')
        ->and($segment->duration())->toBe(5.5);
});
