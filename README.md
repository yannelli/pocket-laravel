# Pocket Laravel SDK

- [Introduction](#introduction)
- [Installation](#installation)
    - [Configuration](#configuration)
- [Making Requests](#making-requests)
    - [Using the Facade](#using-the-facade)
    - [Using Dependency Injection](#using-dependency-injection)
    - [Multi-Tenant Applications](#multi-tenant-applications)
- [Recordings](#recordings)
    - [Retrieving Recordings](#retrieving-recordings)
    - [Filtering Recordings](#filtering-recordings)
    - [Iterating All Recordings](#iterating-all-recordings)
    - [Recording Details](#recording-details)
    - [Transcripts](#transcripts)
    - [Summaries](#summaries)
    - [Action Items](#action-items)
    - [Recording States](#recording-states)
- [Folders](#folders)
- [Tags](#tags)
- [Audio](#audio)
    - [Retrieving Audio URLs](#retrieving-audio-urls)
    - [Downloading Audio](#downloading-audio)
    - [Scoped Audio Resource](#scoped-audio-resource)
- [Error Handling](#error-handling)
- [Configuration Reference](#configuration-reference)
- [Testing](#testing)
- [Disclaimer](#disclaimer)

<a name="introduction"></a>
## Introduction

Pocket Laravel SDK provides an expressive, fluent interface for interacting with the [Pocket API](https://public.heypocketai.com). Using this SDK, you may easily access your recordings, transcripts, summaries, and action items from within your Laravel application.

The SDK handles authentication, request building, pagination, retry logic with exponential backoff, and exception mapping—allowing you to focus on building your application rather than managing HTTP requests.

<a name="installation"></a>
## Installation

You may install the Pocket Laravel SDK via the Composer package manager:

```bash
composer require yannelli/pocket-laravel-sdk
```

After installing the package, the service provider will be automatically registered via Laravel's package discovery.

<a name="configuration"></a>
### Configuration

Before using the SDK, you will need to configure your Pocket API credentials. First, publish the configuration file using the `vendor:publish` Artisan command:

```bash
php artisan vendor:publish --tag="pocket-config"
```

This command will publish a `pocket.php` configuration file to your application's `config` directory:

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

Next, add your Pocket API key to your application's `.env` file:

```env
POCKET_API_KEY=pk_your_api_key_here
```

<a name="making-requests"></a>
## Making Requests

<a name="using-the-facade"></a>
### Using the Facade

The SDK provides a `Pocket` facade that allows you to fluently access all available resources:

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

<a name="using-dependency-injection"></a>
### Using Dependency Injection

If you prefer dependency injection, you may type-hint the `Pocket` class in your controller's constructor or method signatures. The SDK's service provider binds the `Pocket` class as a singleton, ensuring the same instance is resolved throughout your application:

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

<a name="multi-tenant-applications"></a>
### Multi-Tenant Applications

For multi-tenant applications where each tenant may have their own Pocket API key, you may use the `withApiKey` method to create a new SDK instance with a different API key. All other configuration options are preserved from the original instance:

```php
use Yannelli\Pocket\Facades\Pocket;

// Create a tenant-specific instance
$tenantPocket = Pocket::withApiKey($tenant->pocket_api_key);

// Use the tenant-specific instance
$recordings = $tenantPocket->recordings()->list();
```

You may also create multiple tenant instances from a single base configuration:

```php
use Yannelli\Pocket\Pocket;

class TenantRecordingService
{
    public function __construct(
        private Pocket $pocket
    ) {}

    public function getRecordingsForTenant(Tenant $tenant)
    {
        return $this->pocket
            ->withApiKey($tenant->pocket_api_key)
            ->recordings()
            ->list();
    }
}
```

> [!NOTE]
> The `withApiKey` method returns a new `Pocket` instance. The original instance remains unchanged, making it safe to use in concurrent or queued operations.

<a name="recordings"></a>
## Recordings

The recordings resource provides methods for listing, filtering, and retrieving recordings along with their associated transcripts, summaries, and action items.

<a name="retrieving-recordings"></a>
### Retrieving Recordings

To retrieve a paginated list of recordings, you may use the `list` method:

```php
use Yannelli\Pocket\Facades\Pocket;

$recordings = Pocket::recordings()->list();

foreach ($recordings as $recording) {
    echo $recording->title;
    echo $recording->formattedDuration(); // "1:30:45"
}
```

The `list` method returns a `PaginatedRecordings` instance. You may check for additional pages and retrieve subsequent results:

```php
if ($recordings->hasMore()) {
    $nextPage = Pocket::recordings()->list(page: $recordings->nextPage());
}
```

To retrieve a single recording by its ID, use the `get` method:

```php
$recording = Pocket::recordings()->get('rec_123');
```

<a name="filtering-recordings"></a>
### Filtering Recordings

The recordings resource provides several convenient methods for filtering results. You may filter recordings by folder:

```php
$recordings = Pocket::recordings()->inFolder('folder_123');
```

To filter by tags, pass an array of tag IDs to the `withTags` method:

```php
$recordings = Pocket::recordings()->withTags(['tag_1', 'tag_2']);
```

You may also filter recordings within a specific date range:

```php
$recordings = Pocket::recordings()->betweenDates('2025-01-01', '2025-01-31');
```

For more complex filtering, the `list` method accepts all filter parameters directly:

```php
$recordings = Pocket::recordings()->list(
    folderId: 'folder_123',
    startDate: '2025-01-01',
    endDate: '2025-01-31',
    tagIds: ['tag_1', 'tag_2'],
    page: 1,
    limit: 50
);
```

<a name="iterating-all-recordings"></a>
### Iterating All Recordings

When you need to process all recordings, the `all` method returns a generator that automatically handles pagination:

```php
foreach (Pocket::recordings()->all() as $recording) {
    echo $recording->title;
}
```

You may also apply filters when iterating all recordings:

```php
foreach (Pocket::recordings()->all(folderId: 'folder_123') as $recording) {
    // Process recording...
}
```

> [!NOTE]
> The `all` method uses PHP generators to efficiently iterate through large result sets without loading all recordings into memory at once.

<a name="recording-details"></a>
### Recording Details

When retrieving a single recording, you may control which related data is included in the response. By default, all related data is included. To exclude specific data and improve response times:

```php
$recording = Pocket::recordings()->get(
    id: 'rec_123',
    includeTranscript: false,
    includeSummary: true,
    includeActionItems: true
);
```

<a name="transcripts"></a>
### Transcripts

Recordings may include transcript data with speaker identification and time-coded segments. You may access the transcript through the recording instance:

```php
$recording = Pocket::recordings()->get('rec_123');

if ($recording->hasTranscript()) {
    // Access the full transcript text
    echo $recording->transcript->text;

    // Get a list of speakers
    $speakers = $recording->transcript->speakers();

    // Retrieve segments for a specific speaker
    $segments = $recording->transcript->segmentsForSpeaker('Speaker 1');
}
```

<a name="summaries"></a>
### Summaries

Recordings may include AI-generated summaries organized into sections:

```php
if ($recording->hasSummary()) {
    echo $recording->summary->title;

    foreach ($recording->summary->sections as $section) {
        echo $section->heading;
        echo $section->content;
    }
}
```

<a name="action-items"></a>
### Action Items

Recordings may include extracted action items with priority levels and completion status:

```php
if ($recording->hasActionItems()) {
    // Filter by status
    $pendingItems = $recording->pendingActionItems();
    $completedItems = $recording->completedActionItems();

    foreach ($recording->actionItems as $item) {
        echo $item->title;
        echo $item->priority->label(); // "High", "Medium", "Low"

        if ($item->isOverdue()) {
            // Handle overdue item...
        }
    }
}
```

<a name="recording-states"></a>
### Recording States

Recordings progress through various processing states. You may inspect a recording's current state using the following methods:

```php
$recording = Pocket::recordings()->get('rec_123');

if ($recording->isProcessing()) {
    echo "Recording is being processed...";
}

if ($recording->isCompleted()) {
    echo "Recording is ready!";
}

if ($recording->isFailed()) {
    echo "Processing failed: " . $recording->state->description();
}
```

The available recording states are: `pending`, `transcribing`, `failed`, `transcribed`, `summarizing`, `summarization_failed`, `completed`, and `unknown`.

<a name="folders"></a>
## Folders

The folders resource allows you to list and retrieve folders:

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

<a name="tags"></a>
## Tags

The tags resource provides methods for listing and finding tags:

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

<a name="audio"></a>
## Audio

The audio resource provides methods for accessing and downloading audio files associated with recordings.

<a name="retrieving-audio-urls"></a>
### Retrieving Audio URLs

To get a signed URL for a recording's audio file:

```php
use Yannelli\Pocket\Facades\Pocket;

$audioUrl = Pocket::audio()->getUrl('rec_123');

echo $audioUrl->signedUrl;
echo $audioUrl->expiresIn; // Seconds until expiry
```

Signed URLs expire after a period of time. You may check if a URL has expired and retrieve a fresh one:

```php
if ($audioUrl->isExpired()) {
    $audioUrl = Pocket::audio()->getUrl('rec_123');
}
```

<a name="downloading-audio"></a>
### Downloading Audio

The SDK provides several methods for downloading audio content:

```php
// Get the audio file contents as a string
$contents = Pocket::audio()->getContents('rec_123');

// Stream the audio file (memory efficient for large files)
$stream = Pocket::audio()->stream('rec_123');

// Download to a temporary file (auto-cleaned up on script end)
$tempPath = Pocket::audio()->download('rec_123');
```

You may also save audio files directly to Laravel's filesystem:

```php
// Save to a Laravel storage disk
Pocket::audio()->saveTo('recordings/audio.mp3', 's3', [], 'rec_123');

// Save using streaming (memory efficient)
Pocket::audio()->saveStreamTo('recordings/audio.mp3', 'local', [], 'rec_123');

// Save directly to a local path
Pocket::audio()->saveToPath('/path/to/audio.mp3', 'rec_123');
```

To manually clean up temporary files created by the `download` method:

```php
use Yannelli\Pocket\Resources\AudioResource;

AudioResource::cleanup();
```

<a name="scoped-audio-resource"></a>
### Scoped Audio Resource

When working with a single recording's audio, you may create a scoped audio resource to simplify method calls:

```php
$audio = Pocket::audio('rec_123');

// All methods now work without passing the recording ID
$url = $audio->getUrl();
$contents = $audio->getContents();
$tempPath = $audio->download();
```

<a name="error-handling"></a>
## Error Handling

The SDK throws specific exceptions based on the HTTP response status code. You may catch these exceptions to handle different error conditions:

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
    $retryAfter = $e->getRetryAfter();
} catch (ValidationException $e) {
    // Invalid parameters (400)
    $details = $e->getDetails();
} catch (ServerException $e) {
    // Server error (5xx)
} catch (PocketException $e) {
    // Any other API error
}
```

> [!NOTE]
> The `RateLimitException` provides a `getRetryAfter()` method that returns the number of seconds you should wait before making another request.

<a name="configuration-reference"></a>
## Configuration Reference

The following configuration options are available:

| Option | Environment Variable | Default | Description |
|--------|---------------------|---------|-------------|
| `api_key` | `POCKET_API_KEY` | — | Your Pocket API key |
| `base_url` | `POCKET_BASE_URL` | `https://public.heypocketai.com` | API base URL |
| `api_version` | `POCKET_API_VERSION` | `v1` | API version |
| `timeout` | `POCKET_TIMEOUT` | `30` | Request timeout in seconds |
| `retry.times` | `POCKET_RETRY_TIMES` | `3` | Number of retry attempts |
| `retry.sleep` | `POCKET_RETRY_SLEEP` | `1000` | Retry delay in milliseconds |

<a name="testing"></a>
## Testing

To run the SDK's test suite:

```bash
composer test
```

<a name="disclaimer"></a>
## Disclaimer

This is an **unofficial** SDK for the Pocket API, developed and maintained by [Ryan Yannelli](https://ryanyannelli.com). It is not affiliated with, endorsed by, or officially connected to Pocket in any way. Use at your own risk. No warranties or guarantees are provided.

## Requirements

- PHP 8.2 or higher
- Laravel 12.x

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Ryan Yannelli](https://github.com/yannelli)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
