# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run all tests
composer test

# Run a single test file
vendor/bin/pest tests/Feature/RecordingsResourceTest.php

# Run a specific test
vendor/bin/pest --filter "test name here"

# Static analysis (PHPStan level 5)
composer analyse

# Code formatting (Laravel Pint)
composer format

# Test with coverage
composer test-coverage
```

## Architecture

This is a Laravel SDK for the Pocket API (https://public.heypocketai.com). It provides a fluent interface for accessing recordings, transcripts, summaries, and action items.

### Core Components

- **`Pocket`** (`src/Pocket.php`): Main entry point, creates and manages resource instances. Instantiated via `Pocket::fromConfig()` or constructor.

- **`PocketClient`** (`src/PocketClient.php`): HTTP client wrapper using Guzzle. Handles authentication (Bearer token), request building, retry logic with exponential backoff, and exception mapping.

- **`PocketServiceProvider`** (`src/PocketServiceProvider.php`): Laravel service provider using Spatie's package-tools. Registers `Pocket` as a singleton.

### Resources Pattern

Resources in `src/Resources/` encapsulate API endpoints:
- `RecordingsResource` - recordings endpoint with filtering, pagination, and automatic iteration via generators
- `FoldersResource` - folders endpoint
- `TagsResource` - tags endpoint

### Data Objects

DTOs in `src/Data/` represent API responses:
- `Recording`, `Folder`, `Tag` - core entities
- `Transcript`, `TranscriptSegment` - transcript data
- `Summary`, `SummarySection` - summary data
- `ActionItem` - action items with priority/status enums
- `PaginatedRecordings`, `Pagination` - pagination handling

All DTOs have a static `fromArray()` factory method and implement `Arrayable`/`JsonSerializable`.

### Enums

`src/Enums/` contains backed enums for typed values:
- `RecordingState` - processing states (pending, transcribing, completed, etc.)
- `ActionItemPriority` - priority levels
- `ActionItemStatus` - completion status

### Exception Hierarchy

`src/Exceptions/` maps HTTP status codes to specific exceptions:
- `AuthenticationException` (401)
- `NotFoundException` (404)
- `RateLimitException` (429) - includes `getRetryAfter()`
- `ValidationException` (400) - includes `getDetails()`
- `ServerException` (5xx)
- `PocketException` - base class

## Testing

Uses Pest with Orchestra Testbench. Tests are organized as:
- `tests/Feature/` - integration tests for resources and error handling
- `tests/Unit/` - unit tests for DTOs and enums
- `tests/ArchTest.php` - architecture tests

The `MocksHttpResponses` trait (`tests/Helpers/`) provides utilities for mocking Guzzle responses using `MockHandler`.
