<?php

declare(strict_types=1);

namespace PocketLabs\Pocket;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PocketLabs\Pocket\Exceptions\AuthenticationException;
use PocketLabs\Pocket\Exceptions\NotFoundException;
use PocketLabs\Pocket\Exceptions\PocketException;
use PocketLabs\Pocket\Exceptions\RateLimitException;
use PocketLabs\Pocket\Exceptions\ServerException;
use PocketLabs\Pocket\Exceptions\ValidationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class PocketClient
{
    protected Client $httpClient;

    protected string $apiKey;

    protected string $baseUrl;

    protected string $apiVersion;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://app.heypocket.com',
        string $apiVersion = 'v1',
        int $timeout = 30,
        int $retryTimes = 3,
        int $retrySleep = 1000,
        ?HandlerStack $handler = null
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiVersion = $apiVersion;

        $stack = $handler ?? HandlerStack::create();

        if ($handler === null) {
            $stack->push($this->retryMiddleware($retryTimes, $retrySleep));
        }

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $timeout,
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
            ],
        ]);
    }

    protected function retryMiddleware(int $maxRetries, int $delay): callable
    {
        return Middleware::retry(
            function (int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?\Throwable $exception = null) use ($maxRetries): bool {
                // Don't retry if we've exceeded max retries
                if ($retries >= $maxRetries) {
                    return false;
                }

                // Retry on server errors (5xx)
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                // Retry on rate limit (429) with exponential backoff
                if ($response && $response->getStatusCode() === 429) {
                    return true;
                }

                // Retry on connection errors
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                return false;
            },
            function (int $retries) use ($delay): int {
                // Exponential backoff: delay * 2^retries
                return $delay * (int) pow(2, $retries);
            }
        );
    }

    /**
     * Build the full API URL for an endpoint.
     */
    protected function buildUrl(string $endpoint): string
    {
        return "/api/{$this->apiVersion}/public".'/'.ltrim($endpoint, '/');
    }

    /**
     * Make a GET request to the API.
     *
     * @throws PocketException
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => array_filter($query)]);
    }

    /**
     * Make a request to the API.
     *
     * @throws PocketException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $this->buildUrl($endpoint), $options);

            return $this->parseResponse($response);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (GuzzleServerException $e) {
            throw new ServerException('Server error: '.$e->getMessage());
        } catch (GuzzleException $e) {
            throw new PocketException('Request failed: '.$e->getMessage(), 0, [], $e);
        }
    }

    /**
     * Parse the response body.
     *
     * @throws PocketException
     */
    protected function parseResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PocketException('Invalid JSON response from API');
        }

        if (isset($data['success']) && $data['success'] === false) {
            throw PocketException::fromResponse($data, $response->getStatusCode());
        }

        return $data;
    }

    /**
     * Handle client exceptions (4xx errors).
     *
     * @throws PocketException
     */
    protected function handleClientException(ClientException $e): never
    {
        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true) ?? [];

        match ($statusCode) {
            401 => throw new AuthenticationException($body['error'] ?? 'Invalid API key'),
            404 => throw new NotFoundException($body['error'] ?? 'Resource not found'),
            429 => throw new RateLimitException(
                $body['error'] ?? 'Rate limit exceeded',
                $response->hasHeader('Retry-After')
                    ? (int) $response->getHeader('Retry-After')[0]
                    : null
            ),
            400 => throw new ValidationException(
                $body['error'] ?? 'Validation failed',
                $body['details'] ?? []
            ),
            default => throw PocketException::fromResponse($body, $statusCode),
        };
    }

    /**
     * Get the configured API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the configured API version.
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }
}
