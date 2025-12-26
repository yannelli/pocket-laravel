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

    private ?string $recordingId = null;

    /**
     * Create a new AudioResource instance.
     */
    public function __construct(
        protected PocketClient $client,
        ?string $recordingId = null
    ) {
        $this->recordingId = $recordingId;
        $this->httpClient = new Client([
            'timeout' => 300,
        ]);
    }

    /**
     * Get the recording ID, using the instance property if set, or the provided parameter.
     *
     * @throws PocketException
     */
    private function resolveRecordingId(?string $recordingId): string
    {
        $id = $recordingId ?? $this->recordingId;

        if ($id === null) {
            throw new PocketException('Recording ID is required. Either pass it to the method or set it when creating the AudioResource.');
        }

        return $id;
    }

    /**
     * Get the signed URL for a recording's audio file.
     *
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws Exception
     */
    public function getUrl(?string $recordingId = null): AudioUrl
    {
        $id = $this->resolveRecordingId($recordingId);
        $response = $this->client->get("recordings/{$id}/audio-url");

        return AudioUrl::fromArray($response['data']);
    }

    /**
     * Get the audio file contents as a string.
     *
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function getContents(?string $recordingId = null): string
    {
        $audioUrl = $this->getUrl($recordingId);

        $response = $this->httpClient->get($audioUrl->signedUrl);

        return (string) $response->getBody();
    }

    /**
     * Get a stream for the audio file.
     *
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function stream(?string $recordingId = null): StreamInterface
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
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     * @return string The path to the temporary file
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function download(?string $recordingId = null): string
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
     * @param  string  $path  The path where the file should be saved
     * @param  string|null  $disk  The disk to use (null for default)
     * @param  array<string, mixed>  $options  Additional options for the storage
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveTo(
        string $path,
        ?string $disk = null,
        array $options = [],
        ?string $recordingId = null
    ): bool {
        $contents = $this->getContents($recordingId);

        $storage = $disk ? Storage::disk($disk) : Storage::disk();

        return $storage->put($path, $contents, $options);
    }

    /**
     * Save the audio file using a stream (memory efficient for large files).
     *
     * @param  string  $path  The path where the file should be saved
     * @param  string|null  $disk  The disk to use (null for default)
     * @param  array<string, mixed>  $options  Additional options for the storage
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveStreamTo(
        string $path,
        ?string $disk = null,
        array $options = [],
        ?string $recordingId = null
    ): bool {
        $stream = $this->stream($recordingId);

        $storage = $disk ? Storage::disk($disk) : Storage::disk();

        return $storage->put($path, $stream->detach(), $options);
    }

    /**
     * Save the audio file to a local path (without using Laravel's filesystem).
     *
     * @param  string  $path  The absolute path where the file should be saved
     * @param  string|null  $recordingId  The recording ID (optional if set on instance)
     *
     * @throws PocketException
     * @throws GuzzleException
     */
    public function saveToPath(string $path, ?string $recordingId = null): bool
    {
        $audioUrl = $this->getUrl($recordingId);

        $this->httpClient->get($audioUrl->signedUrl, [
            'sink' => $path,
        ]);

        return file_exists($path);
    }
}
