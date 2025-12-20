<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Enums;

enum RecordingState: string
{
    case Pending = 'pending';
    case Transcribing = 'transcribing';
    case Failed = 'failed';
    case Transcribed = 'transcribed';
    case Summarizing = 'summarizing';
    case SummarizationFailed = 'summarization_failed';
    case Completed = 'completed';
    case Unknown = 'unknown';

    /**
     * Get the human-readable label for this state.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Transcribing => 'Transcribing',
            self::Failed => 'Transcription Failed',
            self::Transcribed => 'Transcribed',
            self::Summarizing => 'Summarizing',
            self::SummarizationFailed => 'Summarization Failed',
            self::Completed => 'Completed',
            self::Unknown => 'Unknown',
        };
    }

    /**
     * Get the description of this state.
     *
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'Recording uploaded, transcription pending',
            self::Transcribing => 'Transcription in progress',
            self::Failed => 'Transcription failed',
            self::Transcribed => 'Transcription complete, summarization pending',
            self::Summarizing => 'Summarization in progress',
            self::SummarizationFailed => 'Summarization failed',
            self::Completed => 'Fully processed',
            self::Unknown => 'Unknown state',
        };
    }

    /**
     * Check if the state indicates processing is in progress.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::Pending, self::Transcribing, self::Summarizing]);
    }

    /**
     * Check if the state indicates a failure.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return in_array($this, [self::Failed, self::SummarizationFailed]);
    }

    /**
     * Check if the state indicates completion.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
