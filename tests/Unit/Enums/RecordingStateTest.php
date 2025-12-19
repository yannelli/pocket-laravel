<?php

use PocketLabs\Pocket\Enums\RecordingState;

it('has all recording states', function () {
    expect(RecordingState::cases())->toHaveCount(8);
});

it('can get label for each state', function () {
    expect(RecordingState::Pending->label())->toBe('Pending')
        ->and(RecordingState::Transcribing->label())->toBe('Transcribing')
        ->and(RecordingState::Failed->label())->toBe('Transcription Failed')
        ->and(RecordingState::Transcribed->label())->toBe('Transcribed')
        ->and(RecordingState::Summarizing->label())->toBe('Summarizing')
        ->and(RecordingState::SummarizationFailed->label())->toBe('Summarization Failed')
        ->and(RecordingState::Completed->label())->toBe('Completed')
        ->and(RecordingState::Unknown->label())->toBe('Unknown');
});

it('can get description for each state', function () {
    expect(RecordingState::Pending->description())->toBe('Recording uploaded, transcription pending')
        ->and(RecordingState::Completed->description())->toBe('Fully processed');
});

it('can check if state is processing', function () {
    expect(RecordingState::Pending->isProcessing())->toBeTrue()
        ->and(RecordingState::Transcribing->isProcessing())->toBeTrue()
        ->and(RecordingState::Summarizing->isProcessing())->toBeTrue()
        ->and(RecordingState::Completed->isProcessing())->toBeFalse()
        ->and(RecordingState::Failed->isProcessing())->toBeFalse();
});

it('can check if state is failed', function () {
    expect(RecordingState::Failed->isFailed())->toBeTrue()
        ->and(RecordingState::SummarizationFailed->isFailed())->toBeTrue()
        ->and(RecordingState::Completed->isFailed())->toBeFalse()
        ->and(RecordingState::Pending->isFailed())->toBeFalse();
});

it('can check if state is completed', function () {
    expect(RecordingState::Completed->isCompleted())->toBeTrue()
        ->and(RecordingState::Pending->isCompleted())->toBeFalse()
        ->and(RecordingState::Transcribed->isCompleted())->toBeFalse();
});
