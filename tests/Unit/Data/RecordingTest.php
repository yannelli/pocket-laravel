<?php

use PocketLabs\Pocket\Data\ActionItem;
use PocketLabs\Pocket\Data\Recording;
use PocketLabs\Pocket\Data\Summary;
use PocketLabs\Pocket\Data\Tag;
use PocketLabs\Pocket\Data\Transcript;
use PocketLabs\Pocket\Enums\RecordingState;

it('can create a recording from array', function () {
    $recording = Recording::fromArray([
        'id' => 'rec_123',
        'title' => 'Meeting Recording',
        'folder_id' => 'folder_456',
        'duration' => 3600,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:00:00Z',
        'tags' => [
            ['id' => 'tag_789', 'name' => 'Important', 'color' => '#ff0000'],
        ],
    ]);

    expect($recording->id)->toBe('rec_123')
        ->and($recording->title)->toBe('Meeting Recording')
        ->and($recording->folderId)->toBe('folder_456')
        ->and($recording->duration)->toBe(3600)
        ->and($recording->state)->toBe(RecordingState::Completed)
        ->and($recording->language)->toBe('en')
        ->and($recording->tags)->toHaveCount(1)
        ->and($recording->tags[0])->toBeInstanceOf(Tag::class);
});

it('can create a recording with transcript and summary', function () {
    $recording = Recording::fromArray([
        'id' => 'rec_123',
        'title' => 'Meeting',
        'folder_id' => null,
        'duration' => 1800,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:00:00Z',
        'transcript' => [
            'text' => 'Full transcript text...',
            'segments' => [
                ['start' => 0, 'end' => 5.5, 'text' => 'Hello everyone', 'speaker' => 'Speaker 1'],
            ],
        ],
        'summary' => [
            'title' => 'Q4 Planning Meeting',
            'sections' => [
                ['heading' => 'Key Discussion Points', 'content' => 'The team discussed...'],
            ],
        ],
        'action_items' => [
            [
                'id' => 'ai_001',
                'title' => 'Review budget proposal',
                'description' => 'Review and approve the Q1 budget',
                'status' => 'pending',
                'priority' => 'high',
                'due_date' => '2025-01-20',
            ],
        ],
    ]);

    expect($recording->hasTranscript())->toBeTrue()
        ->and($recording->hasSummary())->toBeTrue()
        ->and($recording->hasActionItems())->toBeTrue()
        ->and($recording->transcript)->toBeInstanceOf(Transcript::class)
        ->and($recording->summary)->toBeInstanceOf(Summary::class)
        ->and($recording->actionItems[0])->toBeInstanceOf(ActionItem::class);
});

it('can format duration correctly', function () {
    $shortRecording = Recording::fromArray([
        'id' => 'rec_1',
        'title' => 'Short',
        'folder_id' => null,
        'duration' => 65,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T10:31:05Z',
    ]);

    $longRecording = Recording::fromArray([
        'id' => 'rec_2',
        'title' => 'Long',
        'folder_id' => null,
        'duration' => 3665,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:31:05Z',
    ]);

    expect($shortRecording->formattedDuration())->toBe('1:05')
        ->and($longRecording->formattedDuration())->toBe('1:01:05');
});

it('can check recording state', function () {
    $pendingRecording = Recording::fromArray([
        'id' => 'rec_1',
        'title' => 'Pending',
        'folder_id' => null,
        'duration' => 0,
        'state' => 'pending',
        'language' => null,
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T10:30:00Z',
    ]);

    $completedRecording = Recording::fromArray([
        'id' => 'rec_2',
        'title' => 'Completed',
        'folder_id' => null,
        'duration' => 3600,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:30:00Z',
    ]);

    $failedRecording = Recording::fromArray([
        'id' => 'rec_3',
        'title' => 'Failed',
        'folder_id' => null,
        'duration' => 0,
        'state' => 'failed',
        'language' => null,
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T10:30:00Z',
    ]);

    expect($pendingRecording->isProcessing())->toBeTrue()
        ->and($pendingRecording->isCompleted())->toBeFalse()
        ->and($completedRecording->isCompleted())->toBeTrue()
        ->and($completedRecording->isProcessing())->toBeFalse()
        ->and($failedRecording->isFailed())->toBeTrue();
});

it('can filter action items by status', function () {
    $recording = Recording::fromArray([
        'id' => 'rec_1',
        'title' => 'Meeting',
        'folder_id' => null,
        'duration' => 3600,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:30:00Z',
        'action_items' => [
            ['id' => 'ai_1', 'title' => 'Task 1', 'status' => 'pending', 'priority' => 'high'],
            ['id' => 'ai_2', 'title' => 'Task 2', 'status' => 'completed', 'priority' => 'medium'],
            ['id' => 'ai_3', 'title' => 'Task 3', 'status' => 'pending', 'priority' => 'low'],
        ],
    ]);

    expect($recording->pendingActionItems())->toHaveCount(2)
        ->and($recording->completedActionItems())->toHaveCount(1);
});

it('can convert recording to array', function () {
    $recording = Recording::fromArray([
        'id' => 'rec_123',
        'title' => 'Meeting Recording',
        'folder_id' => 'folder_456',
        'duration' => 3600,
        'state' => 'completed',
        'language' => 'en',
        'created_at' => '2025-01-15T10:30:00Z',
        'updated_at' => '2025-01-15T11:00:00Z',
        'tags' => [
            ['id' => 'tag_789', 'name' => 'Important', 'color' => '#ff0000'],
        ],
    ]);

    $array = $recording->toArray();

    expect($array['id'])->toBe('rec_123')
        ->and($array['title'])->toBe('Meeting Recording')
        ->and($array['state'])->toBe('completed')
        ->and($array['tags'])->toHaveCount(1);
});
