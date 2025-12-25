<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\RecordingsResource;

function createMockPocketClient(array $responses, &$history = []): PocketClient
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

function successJsonResponse(array $data, int $status = 200, array $headers = []): Response
{
    return new Response(
        $status,
        array_merge(['Content-Type' => 'application/json'], $headers),
        json_encode($data)
    );
}

describe('PocketClient', function () {
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
