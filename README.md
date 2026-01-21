# Pocket Laravel SDK

[![run-tests](https://github.com/yannelli/pocket-laravel-sdk/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/yannelli/pocket-laravel-sdk/actions/workflows/run-tests.yml)

This is an unofficial Laravel SDK for the [Pocket API](https://public.heypocketai.com). Access your recordings, transcripts, summaries, and action items with a clean, fluent interface.

## Disclaimer

This is an **unofficial** SDK for the Pocket API, developed and maintained by [Ryan Yannelli](https://ryanyannelli.com). It is not affiliated with, endorsed by, or officially connected to Pocket in any way. Use at your own risk. No warranties or guarantees are provided.

## Requirements

- PHP 8.2 or higher
- Laravel 12.x

## Installation

Install the package via Composer:

```bash
composer require yannelli/pocket-laravel-sdk
```

The package will automatically register its service provider.

### Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="pocket-config"
```

This publishes `config/pocket.php`:

```php
return [
    'api_key' => env('POCKET_API_KEY'),
    'base_url' => env('POCKET_BASE_URL', 'https://public.heypocketai.com'),
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
use Yannelli\Pocket\Facades\Pocket;

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
use Yannelli\Pocket\Pocket;

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
use Yannelli\Pocket\Facades\Pocket;

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
use Yannelli\Pocket\Facades\Pocket;

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
use Yannelli\Pocket\Facades\Pocket;

// List all tags (ordered by usage)
$tags = Pocket::tags()->list();

// Get most used tags
$topTags = Pocket::tags()->mostUsed(5);

// Find a tag by ID
$tag = Pocket::tags()->find('tag_123');

// Find a tag by name
$tag = Pocket::tags()->findByName('Important');
```

## Audio

Access and download audio files for recordings:

```php
use Yannelli\Pocket\Facades\Pocket;

// Get a signed URL for the audio file
$audioUrl = Pocket::audio()->getUrl('rec_123');
echo $audioUrl->signedUrl;
echo $audioUrl->expiresIn; // seconds until expiry

// Check if the URL has expired
if ($audioUrl->isExpired()) {
    $audioUrl = Pocket::audio()->getUrl('rec_123');
}

// Get the audio file contents as a string
$contents = Pocket::audio()->getContents('rec_123');

// Stream the audio file (memory efficient for large files)
$stream = Pocket::audio()->stream('rec_123');

// Download to a temporary file (auto-cleaned up on script end)
$tempPath = Pocket::audio()->download('rec_123');

// Save to Laravel storage disk
Pocket::audio()->saveTo('recordings/audio.mp3', 's3', [], 'rec_123');

// Save using streaming (memory efficient)
Pocket::audio()->saveStreamTo('recordings/audio.mp3', 'local', [], 'rec_123');

// Save directly to a local path
Pocket::audio()->saveToPath('/path/to/audio.mp3', 'rec_123');
```

### Scoped Audio Resource

You can scope the audio resource to a specific recording:

```php
// Create a scoped audio resource
$audio = Pocket::audio('rec_123');

// All methods now work without passing the recording ID
$url = $audio->getUrl();
$contents = $audio->getContents();
$tempPath = $audio->download();
```

### Cleanup Temporary Files

```php
use Yannelli\Pocket\Resources\AudioResource;

// Manually clean up temporary files created by download()
AudioResource::cleanup();
```

## Data Objects

All API responses are returned as strongly-typed data objects:

```php
use Yannelli\Pocket\Data\Recording;
use Yannelli\Pocket\Data\Folder;
use Yannelli\Pocket\Data\Tag;
use Yannelli\Pocket\Data\Transcript;
use Yannelli\Pocket\Data\Summary;
use Yannelli\Pocket\Data\ActionItem;
use Yannelli\Pocket\Data\AudioUrl;

// All objects implement Arrayable and JsonSerializable
$array = $recording->toArray();
$json = json_encode($recording);
```

## Error Handling

```php
use Yannelli\Pocket\Facades\Pocket;
use Yannelli\Pocket\Exceptions\AuthenticationException;
use Yannelli\Pocket\Exceptions\NotFoundException;
use Yannelli\Pocket\Exceptions\RateLimitException;
use Yannelli\Pocket\Exceptions\ValidationException;
use Yannelli\Pocket\Exceptions\ServerException;
use Yannelli\Pocket\Exceptions\PocketException;

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
| `base_url` | `POCKET_BASE_URL` | `https://public.heypocketai.com` | API base URL |
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


## Credits

- [Ryan Yannelli](https://github.com/yannelli)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
