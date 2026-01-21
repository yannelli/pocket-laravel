<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Exceptions\AuthenticationException;
use Yannelli\Pocket\Exceptions\NotFoundException;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\Exceptions\RateLimitException;
use Yannelli\Pocket\Exceptions\ServerException;
use Yannelli\Pocket\Exceptions\ValidationException;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\RecordingsResource;

function createErrorMockClient(array $responses, array &$history = []): PocketClient
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    return new PocketClient(
        apiKey: 'pk_test_key',
        baseUrl: 'https://https://public.heypocket.com',
        apiVersion: 'v1',
        handler: $handlerStack
    );
}

function errorJsonResponse(array $data, int $status, array $headers = []): Response
{
    return new Response(
        $status,
        array_merge(['Content-Type' => 'application/json'], $headers),
        json_encode($data)
    );
}

describe('Error Handling', function () {
    it('throws AuthenticationException on 401', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Invalid API key',
            ], 401),
        ]);

        $resource = new RecordingsResource($client);

        expect(fn () => $resource->list())
            ->toThrow(AuthenticationException::class, 'Invalid API key');
    });

    it('throws NotFoundException on 404', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Recording not found',
            ], 404),
        ]);

        $resource = new RecordingsResource($client);

        expect(fn () => $resource->get('nonexistent'))
            ->toThrow(NotFoundException::class, 'Recording not found');
    });

    it('throws RateLimitException on 429', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Rate limit exceeded',
            ], 429, ['Retry-After' => '60']),
        ]);

        $resource = new RecordingsResource($client);

        try {
            $resource->list();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            expect($e->getMessage())->toBe('Rate limit exceeded')
                ->and($e->getRetryAfter())->toBe(60);
        }
    });

    it('throws RateLimitException without Retry-After header', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Too many requests',
            ], 429),
        ]);

        $resource = new RecordingsResource($client);

        try {
            $resource->list();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            expect($e->getMessage())->toBe('Too many requests')
                ->and($e->getRetryAfter())->toBeNull();
        }
    });

    it('throws ValidationException on 400', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Validation failed',
                'details' => [
                    'start_date' => ['Invalid date format'],
                    'limit' => ['Must be between 1 and 100'],
                ],
            ], 400),
        ]);

        $resource = new RecordingsResource($client);

        try {
            $resource->list();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            expect($e->getMessage())->toBe('Validation failed')
                ->and($e->getDetails())->toHaveKey('start_date')
                ->and($e->getDetails())->toHaveKey('limit');
        }
    });

    it('throws ServerException on 500', function () {
        $client = createErrorMockClient([
            new Response(500, [], 'Internal Server Error'),
        ]);

        $resource = new RecordingsResource($client);

        expect(fn () => $resource->list())
            ->toThrow(ServerException::class);
    });

    it('throws PocketException on unknown error codes', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Payment required',
            ], 402),
        ]);

        $resource = new RecordingsResource($client);

        try {
            $resource->list();
            $this->fail('Expected PocketException');
        } catch (PocketException $e) {
            expect($e->getMessage())->toBe('Payment required')
                ->and($e->getCode())->toBe(402);
        }
    });

    it('throws PocketException on invalid JSON response', function () {
        $client = createErrorMockClient([
            new Response(200, ['Content-Type' => 'application/json'], 'not valid json'),
        ]);

        $resource = new RecordingsResource($client);

        expect(fn () => $resource->list())
            ->toThrow(PocketException::class, 'Invalid JSON response from API');
    });

    it('throws PocketException when success is false in response', function () {
        $client = createErrorMockClient([
            errorJsonResponse([
                'success' => false,
                'error' => 'Something went wrong',
                'details' => ['extra' => 'info'],
            ], 200),
        ]);

        $resource = new RecordingsResource($client);

        try {
            $resource->list();
            $this->fail('Expected PocketException');
        } catch (PocketException $e) {
            expect($e->getMessage())->toBe('Something went wrong')
                ->and($e->getDetails())->toBe(['extra' => 'info']);
        }
    });
});

describe('Request Headers', function () {
    it('sends correct authorization header', function () {
        $history = [];
        $client = createErrorMockClient([
            errorJsonResponse(['success' => true, 'data' => [], 'pagination' => [
                'page' => 1, 'limit' => 20, 'total' => 0, 'total_pages' => 0, 'has_more' => false,
            ]], 200),
        ], $history);

        $resource = new RecordingsResource($client);
        $resource->list();

        $request = $history[0]['request'];
        expect($request->getHeader('Authorization')[0])->toBe('Bearer pk_test_key')
            ->and($request->getHeader('Accept')[0])->toBe('application/json')
            ->and($request->getHeader('Content-Type')[0])->toBe('application/json');
    });
});
