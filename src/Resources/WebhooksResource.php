<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Resources;

use JsonException;
use Yannelli\Pocket\Data\WebhookEvent;
use Yannelli\Pocket\Exceptions\PocketException;

class WebhooksResource
{
    public const SIGNATURE_HEADER = 'X-HeyPocket-Signature';

    public const TIMESTAMP_HEADER = 'X-HeyPocket-Timestamp';

    /**
     * Verify a Pocket webhook HMAC-SHA256 signature.
     *
     * Pocket signs `{timestamp}.{rawBody}` with the webhook secret and sends
     * the hex digest in the `X-HeyPocket-Signature` header.
     */
    public function verify(string $secret, string $payload, ?string $signature, ?string $timestamp): bool
    {
        if ($secret === '' || $signature === null || $signature === '' || $timestamp === null || $timestamp === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        if (str_starts_with(strtolower($signature), 'sha256=')) {
            $signature = substr($signature, 7);
        }

        return hash_equals($expected, $signature);
    }

    /**
     * Parse a webhook JSON payload.
     *
     * @throws PocketException
     */
    public function parse(string $payload): WebhookEvent
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new PocketException('Invalid JSON webhook payload', 0, [], $e);
        }

        if (! is_array($data)) {
            throw new PocketException('Invalid JSON webhook payload');
        }

        return WebhookEvent::fromArray($data);
    }

    /**
     * Verify the signature and parse the webhook payload.
     *
     * @throws PocketException
     */
    public function parseAndVerify(string $secret, string $payload, ?string $signature, ?string $timestamp): WebhookEvent
    {
        if (! $this->verify($secret, $payload, $signature, $timestamp)) {
            throw new PocketException('Invalid webhook signature');
        }

        return $this->parse($payload);
    }
}
