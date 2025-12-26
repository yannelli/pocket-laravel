<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
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
        baseUrl: 'https://app.heypocket.com',
        apiVersion: 'v1',
        handler: $handlerStack
    );
}

function audioJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
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
});
