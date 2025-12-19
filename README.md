# Pocket Laravel SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pocket-labs/pocket-laravel.svg?style=flat-square)](https://packagist.org/packages/pocket-labs/pocket-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pocket-labs/pocket-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pocket-labs/pocket-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/pocket-labs/pocket-laravel.svg?style=flat-square)](https://packagist.org/packages/pocket-labs/pocket-laravel)

The official Laravel SDK for the [Pocket API](https://app.heypocket.com). Access your recordings, transcripts, summaries, and action items with a clean, fluent interface.

## Installation

You can install the package via composer:

```bash
composer require pocket-labs/pocket-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="pocket-config"
```

This is the contents of the published config file:

```php
return [
    'api_key' => env('POCKET_API_KEY'),
    'base_url' => env('POCKET_BASE_URL', 'https://app.heypocket.com'),
    'api_version' => env('POCKET_API_VERSION', 'v1'),
    'timeout' => env('POCKET_TIMEOUT', 30),
    'retry' => [
        'times' => env('POCKET_RETRY_TIMES', 3),
        'sleep' => env('POCKET_RETRY_SLEEP', 1000),
    ],
];
```

Add your Pocket API key to your `.env` file:

```env
POCKET_API_KEY=pk_your_api_key_here
```

## Usage

### Using the Facade

```php
use PocketLabs\Pocket\Facades\Pocket;

// List recordings
$recordings = Pocket::recordings()->list();

// Get a specific recording
$recording = Pocket::recordings()->get('rec_123');

// List folders
$folders = Pocket::folders()->list();

// List tags
$tags = Pocket::tags()->list();
```

### Using Dependency Injection

```php
use PocketLabs\Pocket\Pocket;

class RecordingController extends Controller
{
    public function __construct(
        private Pocket $pocket
    ) {}

    public function index()
    {
        return $this->pocket->recordings()->list();
    }
}
```

## Recordings

### List Recordings

```php
use PocketLabs\Pocket\Facades\Pocket;

// Basic listing with pagination
$recordings = Pocket::recordings()->list();

foreach ($recordings as $recording) {
    echo $recording->title;
    echo $recording->formattedDuration(); // "1:30:45"
}

// Check pagination
if ($recordings->hasMore()) {
    $nextPage = Pocket::recordings()->list(page: $recordings->nextPage());
}
```

### Filter Recordings

```php
// By folder
$recordings = Pocket::recordings()->inFolder('folder_123');

// By tags
$recordings = Pocket::recordings()->withTags(['tag_1', 'tag_2']);

// By date range
$recordings = Pocket::recordings()->betweenDates('2025-01-01', '2025-01-31');

// Combined filters
$recordings = Pocket::recordings()->list(
    folderId: 'folder_123',
    startDate: '2025-01-01',
    endDate: '2025-01-31',
    tagIds: ['tag_1', 'tag_2'],
    page: 1,
    limit: 50
);
```

### Iterate All Recordings

```php
// Automatically handles pagination
foreach (Pocket::recordings()->all() as $recording) {
    echo $recording->title;
}

// With filters
foreach (Pocket::recordings()->all(folderId: 'folder_123') as $recording) {
    // Process recording
}
```

### Get Recording Details

```php
$recording = Pocket::recordings()->get('rec_123');

// Access transcript
if ($recording->hasTranscript()) {
    echo $recording->transcript->text;

    // Get speakers
    $speakers = $recording->transcript->speakers();

    // Get segments for a specific speaker
    $segments = $recording->transcript->segmentsForSpeaker('Speaker 1');
}

// Access summary
if ($recording->hasSummary()) {
    echo $recording->summary->title;

    foreach ($recording->summary->sections as $section) {
        echo $section->heading;
        echo $section->content;
    }
}

// Access action items
if ($recording->hasActionItems()) {
    $pendingItems = $recording->pendingActionItems();
    $completedItems = $recording->completedActionItems();

    foreach ($recording->actionItems as $item) {
        echo $item->title;
        echo $item->priority->label(); // "High", "Medium", etc.

        if ($item->isOverdue()) {
            // Handle overdue item
        }
    }
}
```

### Control What's Included

```php
// Get recording without transcript (faster)
$recording = Pocket::recordings()->get(
    id: 'rec_123',
    includeTranscript: false,
    includeSummary: true,
    includeActionItems: true
);
```

### Check Recording State

```php
$recording = Pocket::recordings()->get('rec_123');

if ($recording->isProcessing()) {
    echo "Still processing...";
}

if ($recording->isCompleted()) {
    echo "Ready!";
}

if ($recording->isFailed()) {
    echo "Processing failed: " . $recording->state->description();
}

// All states: pending, transcribing, failed, transcribed,
// summarizing, summarization_failed, completed, unknown
```

## Folders

```php
use PocketLabs\Pocket\Facades\Pocket;

// List all folders
$folders = Pocket::folders()->list();

// Find a folder by ID
$folder = Pocket::folders()->find('folder_123');

// Find a folder by name
$folder = Pocket::folders()->findByName('Work Meetings');

// Get the default folder
$defaultFolder = Pocket::folders()->default();
```

## Tags

```php
use PocketLabs\Pocket\Facades\Pocket;

// List all tags (ordered by usage)
$tags = Pocket::tags()->list();

// Get most used tags
$topTags = Pocket::tags()->mostUsed(5);

// Find a tag by ID
$tag = Pocket::tags()->find('tag_123');

// Find a tag by name
$tag = Pocket::tags()->findByName('Important');
```

## Data Objects

All API responses are returned as strongly-typed data objects:

```php
use PocketLabs\Pocket\Data\Recording;
use PocketLabs\Pocket\Data\Folder;
use PocketLabs\Pocket\Data\Tag;
use PocketLabs\Pocket\Data\Transcript;
use PocketLabs\Pocket\Data\Summary;
use PocketLabs\Pocket\Data\ActionItem;

// All objects implement Arrayable and JsonSerializable
$array = $recording->toArray();
$json = json_encode($recording);
```

## Error Handling

```php
use PocketLabs\Pocket\Facades\Pocket;
use PocketLabs\Pocket\Exceptions\AuthenticationException;
use PocketLabs\Pocket\Exceptions\NotFoundException;
use PocketLabs\Pocket\Exceptions\RateLimitException;
use PocketLabs\Pocket\Exceptions\ValidationException;
use PocketLabs\Pocket\Exceptions\ServerException;
use PocketLabs\Pocket\Exceptions\PocketException;

try {
    $recording = Pocket::recordings()->get('rec_123');
} catch (AuthenticationException $e) {
    // Invalid API key (401)
} catch (NotFoundException $e) {
    // Recording not found (404)
} catch (RateLimitException $e) {
    // Too many requests (429)
    $retryAfter = $e->getRetryAfter(); // seconds to wait
} catch (ValidationException $e) {
    // Invalid parameters (400)
    $details = $e->getDetails();
} catch (ServerException $e) {
    // Server error (500)
} catch (PocketException $e) {
    // Any other API error
}
```

## Configuration Options

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| `api_key` | `POCKET_API_KEY` | - | Your Pocket API key |
| `base_url` | `POCKET_BASE_URL` | `https://app.heypocket.com` | API base URL |
| `api_version` | `POCKET_API_VERSION` | `v1` | API version |
| `timeout` | `POCKET_TIMEOUT` | `30` | Request timeout in seconds |
| `retry.times` | `POCKET_RETRY_TIMES` | `3` | Number of retry attempts |
| `retry.sleep` | `POCKET_RETRY_SLEEP` | `1000` | Retry delay in milliseconds |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Pocket Labs](https://github.com/pocket-labs)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
