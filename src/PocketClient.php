<?php

declare(strict_types=1);

namespace Yannelli\Pocket;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yannelli\Pocket\Exceptions\AuthenticationException;
use Yannelli\Pocket\Exceptions\NotFoundException;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\Exceptions\RateLimitException;
use Yannelli\Pocket\Exceptions\ServerException;
use Yannelli\Pocket\Exceptions\ValidationException;

class PocketClient
{
    protected Client $httpClient;

    protected string $apiKey;

    protected string $baseUrl;

    protected string $apiVersion;

    protected ?HandlerStack $handler;

    protected ?ResponseInterface $response = null;

    protected ?RequestInterface $request = null;

    /**
     * Create a new PocketClient instance.
     *
     * @param  string  $apiKey  The Pocket API key
     * @param  string  $baseUrl  The base URL for the Pocket API
     * @param  string  $apiVersion  The API version to use
     * @param  int  $timeout  Request timeout in seconds
     * @param  int  $retryTimes  Number of times to retry failed requests
     * @param  int  $retrySleep  Base sleep time in milliseconds between retries
     * @param  HandlerStack|null  $handler  Optional custom Guzzle handler stack
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://production.heypocketai.com',
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

        $stack->push($this->captureRequestMiddleware());

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $timeout,
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->apiKey,
                'User-Agent' => 'PocketClient/1.0.0',
            ],
        ]);
    }

    /**
     * Create middleware to capture and store the outgoing HTTP request.
     *
     * @return callable Middleware that assigns the outgoing request to a property for later access
     */
    protected function captureRequestMiddleware(): callable
    {
        return Middleware::mapRequest(function (RequestInterface $request) {
            $this->request = $request;

            return $request;
        });
    }

    /**
     * Create retry middleware for handling transient failures.
     *
     * @param  int  $maxRetries  Maximum number of retry attempts
     * @param  int  $delay  Base delay in milliseconds between retries
     */
    protected function retryMiddleware(int $maxRetries, int $delay): callable
    {
        return Middleware::retry(
            function (int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?Throwable $exception = null) use ($maxRetries): bool {
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
                if ($exception instanceof ConnectException) {
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
     * Make a GET request to the API.
     *
     * @param  string  $endpoint  The API endpoint to request
     * @param  array<string, mixed>  $query  Query parameters to include
     * @return array<string, mixed>
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
     * @param  string  $method  The HTTP method (GET, POST, etc.)
     * @param  string  $endpoint  The API endpoint to request
     * @param  array<string, mixed>  $options  Guzzle request options
     * @return array<string, mixed>
     *
     * @throws PocketException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $this->buildUrl($endpoint), $options);

            $this->response = $response;

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
     * Build the full API URL for an endpoint.
     *
     * @param  string  $endpoint  The API endpoint path
     */
    protected function buildUrl(string $endpoint): string
    {
        return "/api/{$this->apiVersion}/public".'/'.ltrim($endpoint, '/');
    }

    /**
     * Parse the response body.
     *
     * @param  ResponseInterface  $response  The HTTP response to parse
     * @return array<string, mixed>
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

        return array_merge([
            'headers' => $response->getHeaders(),
            'base_url' => $this->request?->getUri(),
            'status_code' => $response->getStatusCode(),
        ], $data);
    }

    /**
     * Handle client exceptions (4xx errors).
     *
     * @param  ClientException  $e  The client exception to handle
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

    /**
     * Retrieve the response instance.
     *
     * @return ResponseInterface|null The response instance if available, or null if no response exists.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
