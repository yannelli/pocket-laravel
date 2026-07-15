<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Yannelli\Pocket\Data\AudioUrl;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\AudioResource;

function createMockAudioClient(array $responses, array &$history = []): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://public.heypocketai.com',
        apiVersion: 'v1',
        retryTimes: 0,
        handler: $handlerStack
    );
}

function audioJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

function audioUrlResponse(): Response
{
    return audioJsonResponse([
        'success' => true,
        'data' => [
            'url' => 'https://example.com/audio.mp3',
            'expires_at' => '2030-01-01T00:00:00Z',
        ],
    ]);
}

function mockAudioDownload(AudioResource $resource, array $responses): void
{
    $property = new ReflectionProperty($resource, 'httpClient');
    $property->setValue($resource, new Client([
        'handler' => HandlerStack::create(new MockHandler($responses)),
    ]));
}

describe('AudioResource', function () {
    it('can get a signed audio URL', function () {
        $history = [];
        $client = createMockAudioClient([
            audioJsonResponse([
                'success' => true,
                'data' => [
                    'signed_url' => 'https://pocket-recording-dev.s3.amazonaws.com/user/date/rec_123.mp3?signature=abc',
                    'expires_in' => 3600,
                    'expires_at' => '2025-12-25T22:52:25.185Z',
                ],
            ]),
        ], $history);

        $resource = new AudioResource($client);
        $audioUrl = $resource->getUrl('rec_123');

        expect($audioUrl)->toBeInstanceOf(AudioUrl::class)
            ->and($audioUrl->signedUrl)->toContain('rec_123.mp3')
            ->and($audioUrl->expiresIn)->toBe(3600);

        // Verify the request
        $request = $history[0]['request'];
        expect($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/recordings/rec_123/audio-url')
            ->and($request->getHeader('Authorization')[0])->toBe('Bearer pk_test_key');
    });

    it('can get audio URL with expiration info', function () {
        $futureDate = (new DateTimeImmutable)->modify('+1 hour')->format('c');
        $history = [];
        $client = createMockAudioClient([
            audioJsonResponse([
                'success' => true,
                'data' => [
                    'signed_url' => 'https://example.com/audio.mp3',
                    'expires_in' => 3600,
                    'expires_at' => $futureDate,
                ],
            ]),
        ], $history);

        $resource = new AudioResource($client);
        $audioUrl = $resource->getUrl('rec_456');

        expect($audioUrl->isExpired())->toBeFalse()
            ->and($audioUrl->secondsUntilExpiry())->toBeGreaterThan(3500);
    });

    it('supports the documented expiration option and url response field', function () {
        $history = [];
        $client = createMockAudioClient([
            audioJsonResponse([
                'success' => true,
                'data' => [
                    'url' => 'https://example.com/audio.mp3',
                    'expires_at' => '2030-01-01T00:00:00Z',
                ],
            ]),
        ], $history);

        $audioUrl = (new AudioResource($client))->getUrl('rec/123', 120);

        parse_str($history[0]['request']->getUri()->getQuery(), $query);

        expect($history[0]['request']->getUri()->getPath())->toBe('/api/v1/public/recordings/rec%2F123/audio-url')
            ->and($query['expires_in'])->toBe('120')
            ->and($audioUrl->signedUrl)->toBe('https://example.com/audio.mp3');
    });

    it('gets audio contents and streams', function () {
        $client = createMockAudioClient([audioUrlResponse(), audioUrlResponse()]);
        $resource = new AudioResource($client, 'rec_123');
        mockAudioDownload($resource, [
            new Response(200, [], 'audio contents'),
            new Response(200, [], 'streamed audio'),
        ]);

        expect($resource->getContents())->toBe('audio contents')
            ->and((string) $resource->stream())->toBe('streamed audio');
    });

    it('downloads and cleans up temporary audio files', function () {
        $client = createMockAudioClient([audioUrlResponse()]);
        $resource = new AudioResource($client, 'rec_123');
        mockAudioDownload($resource, [new Response(200, [], 'temporary audio')]);

        $path = $resource->download();

        expect(file_get_contents($path))->toBe('temporary audio');

        AudioResource::cleanup();

        expect(file_exists($path))->toBeFalse();
    });

    it('saves audio to Laravel filesystem disks', function (bool $streamed) {
        Storage::fake('audio');
        $client = createMockAudioClient([audioUrlResponse()]);
        $resource = new AudioResource($client, 'rec_123');
        mockAudioDownload($resource, [new Response(200, [], 'stored audio')]);

        $saved = $streamed
            ? $resource->saveStreamTo('recordings/audio.mp3', 'audio')
            : $resource->saveTo('recordings/audio.mp3', 'audio');

        expect($saved)->toBeTrue();
        Storage::disk('audio')->assertExists('recordings/audio.mp3');
        expect(Storage::disk('audio')->get('recordings/audio.mp3'))->toBe('stored audio');
    })->with([
        'buffered' => [false],
        'streamed' => [true],
    ]);

    it('saves audio directly to a local path', function () {
        $client = createMockAudioClient([audioUrlResponse()]);
        $resource = new AudioResource($client, 'rec_123');
        mockAudioDownload($resource, [new Response(200, [], 'local audio')]);
        $path = sys_get_temp_dir().'/pocket_audio_resource_test.mp3';

        try {
            expect($resource->saveToPath($path))->toBeTrue()
                ->and(file_get_contents($path))->toBe('local audio');
        } finally {
            @unlink($path);
        }
    });
});
