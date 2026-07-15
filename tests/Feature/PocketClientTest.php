<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Yannelli\Pocket\Exceptions\ServerException;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\RecordingsResource;

function createMockPocketClient(array $responses, &$history = [], int $retryTimes = 0): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://public.heypocketai.com',
        apiVersion: 'v1',
        retryTimes: $retryTimes,
        retrySleep: 0,
        handler: $handlerStack
    );
}

function successJsonResponse(array $data, int $status = 200, array $headers = []): Response
{
    return new Response(
        $status,
        array_merge(['Content-Type' => 'application/json'], $headers),
        json_encode($data)
    );
}

describe('PocketClient', function () {
    it('preserves false and zero query values while omitting nulls', function () {
        $history = [];
        $client = createMockPocketClient([
            successJsonResponse(['success' => true, 'data' => []]),
        ], $history);

        $client->get('query-test', [
            'zero' => 0,
            'false' => false,
            'empty' => '',
            'null' => null,
        ]);

        parse_str($history[0]['request']->getUri()->getQuery(), $query);

        expect($query)->toBe([
            'zero' => '0',
            'false' => '0',
            'empty' => '',
        ]);
    });

    it('retries transient server errors with a custom handler stack', function () {
        $client = createMockPocketClient([
            new Response(500, [], 'Internal Server Error'),
            successJsonResponse([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more' => false,
                ],
            ]),
        ], retryTimes: 1);

        $result = (new RecordingsResource($client))->list();

        expect($result->isEmpty())->toBeTrue()
            ->and($client->getResponse()->getStatusCode())->toBe(200);
    });

    it('does not mutate a shared custom handler stack', function () {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
            new Response(500),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $firstClient = new PocketClient('pk_test_key', retryTimes: 1, retrySleep: 0, handler: $handlerStack);
        new PocketClient('pk_test_key', retryTimes: 1, retrySleep: 0, handler: $handlerStack);

        expect(fn () => $firstClient->get('recordings'))
            ->toThrow(ServerException::class)
            ->and($mock->count())->toBe(2);
    });

    it('handles HTTP-date Retry-After values', function () {
        $client = createMockPocketClient([
            new Response(429, ['Retry-After' => 'Thu, 01 Jan 1970 00:00:00 GMT']),
            successJsonResponse(['success' => true, 'data' => []]),
        ], retryTimes: 1);

        expect($client->get('recordings')['data'])->toBe([]);
    });

    describe('getResponse', function () {
        it('returns null before any request is made', function () {
            $client = createMockPocketClient([
                successJsonResponse(['success' => true, 'data' => []]),
            ]);

            expect($client->getResponse())->toBeNull();
        });

        it('returns the response after a successful request', function () {
            $client = createMockPocketClient([
                successJsonResponse([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'page' => 1,
                        'limit' => 20,
                        'total' => 0,
                        'total_pages' => 0,
                        'has_more' => false,
                    ],
                ]),
            ]);

            $resource = new RecordingsResource($client);
            $resource->list();

            $response = $client->getResponse();

            expect($response)->toBeInstanceOf(ResponseInterface::class)
                ->and($response->getStatusCode())->toBe(200);
        });

        it('returns response with correct headers', function () {
            $client = createMockPocketClient([
                successJsonResponse(
                    [
                        'success' => true,
                        'data' => [],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 20,
                            'total' => 0,
                            'total_pages' => 0,
                            'has_more' => false,
                        ],
                    ],
                    200,
                    ['X-Custom-Header' => 'test-value']
                ),
            ]);

            $resource = new RecordingsResource($client);
            $resource->list();

            $response = $client->getResponse();

            expect($response->getHeader('X-Custom-Header')[0])->toBe('test-value')
                ->and($response->getHeader('Content-Type')[0])->toBe('application/json');
        });

        it('updates response after each request', function () {
            $client = createMockPocketClient([
                successJsonResponse([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'page' => 1,
                        'limit' => 20,
                        'total' => 0,
                        'total_pages' => 0,
                        'has_more' => false,
                    ],
                ], 200, ['X-Request' => 'first']),
                successJsonResponse([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'page' => 1,
                        'limit' => 20,
                        'total' => 0,
                        'total_pages' => 0,
                        'has_more' => false,
                    ],
                ], 200, ['X-Request' => 'second']),
            ]);

            $resource = new RecordingsResource($client);

            $resource->list();
            expect($client->getResponse()->getHeader('X-Request')[0])->toBe('first');

            $resource->list();
            expect($client->getResponse()->getHeader('X-Request')[0])->toBe('second');
        });
    });
});
