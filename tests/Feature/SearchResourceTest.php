<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Data\SearchHit;
use Yannelli\Pocket\Data\SearchResults;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\SearchResource;

function createSearchMockClient(array $responses, array &$history = []): PocketClient
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

function searchJsonResponse(array $data, int $status = 200): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
}

describe('SearchResource', function () {
    it('searches recordings with the documented request body', function () {
        $history = [];
        $client = createSearchMockClient([
            searchJsonResponse([
                'success' => true,
                'data' => [
                    [
                        'recording_id' => 'rec_123',
                        'title' => 'Budget review',
                        'snippet' => 'We should ship v2 by Friday',
                        'score' => 0.91,
                        'type' => 'transcript',
                    ],
                ],
            ]),
        ], $history);

        $results = (new SearchResource($client))->query(
            query: 'ship v2',
            limit: 50,
            dateFrom: '2026-01-01T00:00:00Z',
            dateTo: new DateTimeImmutable('2026-01-31T23:59:59Z'),
            folderIds: ['folder_1'],
            recordingIds: ['rec_123'],
        );

        expect($results)->toBeInstanceOf(SearchResults::class)
            ->and($results->count())->toBe(1)
            ->and($results->first())->toBeInstanceOf(SearchHit::class)
            ->and($results->first()->recordingId)->toBe('rec_123')
            ->and($results->first()->title)->toBe('Budget review')
            ->and($results->query)->toBe('ship v2');

        $request = $history[0]['request'];
        $body = json_decode((string) $request->getBody(), true);

        expect($request->getMethod())->toBe('POST')
            ->and($request->getUri()->getPath())->toBe('/api/v1/public/search')
            ->and($body['query'])->toBe('ship v2')
            ->and($body['limit'])->toBe(20)
            ->and($body['filters']['dateFrom'])->toBe('2026-01-01T00:00:00Z')
            ->and($body['filters']['folderIds'])->toBe(['folder_1'])
            ->and($body['filters']['recordingIds'])->toBe(['rec_123']);
    });

    it('omits empty search filters', function () {
        $history = [];
        $client = createSearchMockClient([
            searchJsonResponse(['success' => true, 'data' => []]),
        ], $history);

        $results = (new SearchResource($client))->query('standup');

        $body = json_decode((string) $history[0]['request']->getBody(), true);

        expect($results->isEmpty())->toBeTrue()
            ->and($body)->toBe([
                'query' => 'standup',
                'limit' => 8,
            ]);
    });
});
