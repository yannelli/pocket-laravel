<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Storage;
use Psr\Http\Message\StreamInterface;
use Yannelli\Pocket\Data\AudioUrl;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\PocketClient;

class AudioResource
{
    protected Client $httpClient;

    /**
     * Create a new AudioResource instance.
     */
    public function __construct(
        protected PocketClient $client
    ) {
        $this->httpClient = new Client([
            'timeout' => 300,
        ]);
    }

    /**
     * Get the signed URL for a recording's audio file.
     *
     * @param  string  $recordingId  The recording ID
     *
     * @throws PocketException
     * @throws Exception
     */
    public function getUrl(string $recordingId): AudioUrl
    {
        $response = $this->client->get("recordings/{$recordingId}/audio-url");

        return AudioUrl::fromArray($response['data']);
    }

    /**
     * Get the audio file contents as a string.
     *
     * @param  string  $recordingId  The recording ID
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function getContents(string $recordingId): string
    {
        $audioUrl = $this->getUrl($recordingId);

        $response = $this->httpClient->get($audioUrl->signedUrl);

        return (string) $response->getBody();
    }

    /**
     * Get a stream for the audio file.
     *
     * @param  string  $recordingId  The recording ID
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function stream(string $recordingId): StreamInterface
    {
        $audioUrl = $this->getUrl($recordingId);

        $response = $this->httpClient->get($audioUrl->signedUrl, [
            'stream' => true,
        ]);

        return $response->getBody();
    }

    /**
     * Download the audio file and return a temporary file path.
     *
     * @param  string  $recordingId  The recording ID
     * @return string The path to the temporary file
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function download(string $recordingId): string
    {
        $audioUrl = $this->getUrl($recordingId);
        $tempPath = sys_get_temp_dir().'/'.uniqid('pocket_audio_', true).'.mp3';

        $this->httpClient->get($audioUrl->signedUrl, [
            'sink' => $tempPath,
        ]);

        return $tempPath;
    }

    /**
     * Save the audio file to a Laravel filesystem disk.
     *
     * @param  string  $recordingId  The recording ID
     * @param  string  $path  The path where the file should be saved
     * @param  string|null  $disk  The disk to use (null for default)
     * @param  array<string, mixed>  $options  Additional options for the storage
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveTo(
        string $recordingId,
        string $path,
        ?string $disk = null,
        array $options = []
    ): bool {
        $contents = $this->getContents($recordingId);

        $storage = $disk ? Storage::disk($disk) : Storage::disk();

        return $storage->put($path, $contents, $options);
    }

    /**
     * Save the audio file using a stream (memory efficient for large files).
     *
     * @param  string  $recordingId  The recording ID
     * @param  string  $path  The path where the file should be saved
     * @param  string|null  $disk  The disk to use (null for default)
     * @param  array<string, mixed>  $options  Additional options for the storage
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveStreamTo(
        string $recordingId,
        string $path,
        ?string $disk = null,
        array $options = []
    ): bool {
        $stream = $this->stream($recordingId);

        $storage = $disk ? Storage::disk($disk) : Storage::disk();

        return $storage->put($path, $stream->detach(), $options);
    }

    /**
     * Save the audio file to a local path (without using Laravel's filesystem).
     *
     * @param  string  $recordingId  The recording ID
     * @param  string  $path  The absolute path where the file should be saved
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveToPath(string $recordingId, string $path): bool
    {
        $audioUrl = $this->getUrl($recordingId);

        $this->httpClient->get($audioUrl->signedUrl, [
            'sink' => $path,
        ]);

        return file_exists($path);
    }
}
