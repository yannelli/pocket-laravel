<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Tests\Helpers;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Yannelli\Pocket\Pocket;
use Yannelli\Pocket\PocketClient;

trait MocksHttpResponses
{
    protected MockHandler $mockHandler;

    protected array $requestHistory = [];

    /**
     * Create a Pocket client with mock responses.
     *
     * @param  array<Response>  $responses
     */
    protected function createMockPocket(array $responses): Pocket
    {
        $this->mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to track requests
        $handlerStack->push(\GuzzleHttp\Middleware::history($this->requestHistory));

        $client = new PocketClient(
            apiKey: 'pk_test_key',
            baseUrl: 'https://app.heypocket.com',
            apiVersion: 'v1',
            timeout: 30,
            retryTimes: 0,
            retrySleep: 0,
            handler: $handlerStack
        );

        return new Pocket(
            apiKey: 'pk_test_key',
            baseUrl: 'https://app.heypocket.com',
            apiVersion: 'v1',
            timeout: 30,
            retryTimes: 0,
            retrySleep: 0
        );
    }

    /**
     * Create a PocketClient with mock responses.
     *
     * @param  array<Response>  $responses
     */
    protected function createMockClient(array $responses): PocketClient
    {
        $this->mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to track requests
        $handlerStack->push(\GuzzleHttp\Middleware::history($this->requestHistory));

        return new PocketClient(
            apiKey: 'pk_test_key',
            baseUrl: 'https://app.heypocket.com',
            apiVersion: 'v1',
            timeout: 30,
            retryTimes: 0,
            retrySleep: 0,
            handler: $handlerStack
        );
    }

    /**
     * Create a JSON response.
     */
    protected function jsonResponse(array $data, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($data)
        );
    }

    /**
     * Get the last request that was made.
     */
    protected function getLastRequest(): ?\Psr\Http\Message\RequestInterface
    {
        $last = end($this->requestHistory);

        return $last ? $last['request'] : null;
    }

    /**
     * Get all requests that were made.
     */
    protected function getRequests(): array
    {
        return array_map(fn ($item) => $item['request'], $this->requestHistory);
    }

    /**
     * Assert that a request was made to a specific endpoint.
     */
    protected function assertRequestMade(string $method, string $path): void
    {
        $request = $this->getLastRequest();

        expect($request)->not->toBeNull()
            ->and($request->getMethod())->toBe($method)
            ->and($request->getUri()->getPath())->toBe($path);
    }

    /**
     * Assert the Authorization header was set correctly.
     */
    protected function assertAuthorizationHeader(string $expectedToken = 'pk_test_key'): void
    {
        $request = $this->getLastRequest();

        expect($request)->not->toBeNull()
            ->and($request->getHeader('Authorization')[0])->toBe('Bearer '.$expectedToken);
    }
}
