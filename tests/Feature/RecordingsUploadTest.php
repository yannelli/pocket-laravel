<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Data\UploadUrl;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\RecordingsResource;

function createUploadMockClient(array $responses, array &$history = []): PocketClient
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

describe('RecordingsResource uploads', function () {
    it('creates an upload URL with the documented fields', function () {
        $history = [];
        $client = createUploadMockClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'success' => true,
                'data' => [
                    'upload_url' => 'https://s3.example.com/upload',
                    'recording_id' => 'rec_new',
                    'content_type' => 'audio/mpeg',
                    'expires_in' => 900,
                ],
            ])),
        ], $history);

        $upload = (new RecordingsResource($client))->createUploadUrl(
            contentType: 'audio/mpeg',
            fileName: 'standup.mp3',
            duration: 1800,
            recordingAt: new DateTimeImmutable('2026-03-15T09:00:00Z'),
            title: 'Team Standup',
        );

        expect($upload)->toBeInstanceOf(UploadUrl::class)
            ->and($upload->signedUrl)->toBe('https://s3.example.com/upload')
            ->and($upload->recordingId)->toBe('rec_new')
            ->and($upload->contentType)->toBe('audio/mpeg');

        $request = $history[0]['request'];
        $body = json_decode((string) $request->getBody(), true);

        expect($request->getMethod())->toBe('POST')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/recordings/upload-url')
            ->and($body['content_type'])->toBe('audio/mpeg')
            ->and($body['file_name'])->toBe('standup.mp3')
            ->and($body['duration'])->toBe(1800)
            ->and($body['title'])->toBe('Team Standup');
    });
});
