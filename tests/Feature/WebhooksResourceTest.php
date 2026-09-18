<?php

use Yannelli\Pocket\Data\WebhookEvent;
use Yannelli\Pocket\Exceptions\PocketException;
use Yannelli\Pocket\Resources\WebhooksResource;

describe('WebhooksResource', function () {
    it('verifies a valid HMAC signature', function () {
        $payload = '{"event":"summary.completed"}';
        $timestamp = '1700000000000';
        $secret = 'whsec_test';
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        expect((new WebhooksResource)->verify($secret, $payload, $signature, $timestamp))->toBeTrue();
    });

    it('accepts a sha256= signature prefix', function () {
        $payload = '{"event":"recording.created"}';
        $timestamp = '1700000000000';
        $secret = 'whsec_test';
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        expect((new WebhooksResource)->verify($secret, $payload, $signature, $timestamp))->toBeTrue();
    });

    it('rejects an invalid signature', function () {
        expect((new WebhooksResource)->verify('secret', '{}', 'deadbeef', '1'))->toBeFalse();
    });

    it('parses a webhook payload', function () {
        $payload = json_encode([
            'event' => 'summary.completed',
            'timestamp' => '2026-02-18T12:00:00.000Z',
            'user' => ['id' => 'user_abc123', 'email' => 'alice@example.com'],
            'organization' => ['id' => 'org_def456'],
            'recording' => ['id' => 'rec_abc123', 'title' => 'Team Standup'],
            'transcript' => [
                ['speaker' => 'Alice', 'text' => 'Hello', 'start' => 0.0, 'end' => 1.0],
            ],
        ], JSON_THROW_ON_ERROR);

        $event = (new WebhooksResource)->parse($payload);

        expect($event)->toBeInstanceOf(WebhookEvent::class)
            ->and($event->event)->toBe('summary.completed')
            ->and($event->recordingId())->toBe('rec_abc123')
            ->and($event->user['email'])->toBe('alice@example.com')
            ->and($event->transcript)->toHaveCount(1);
    });

    it('parses and verifies a signed payload', function () {
        $payload = '{"event":"recording.deleted","recording":{"id":"rec_1"}}';
        $timestamp = '1700000000000';
        $secret = 'whsec_test';
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $event = (new WebhooksResource)->parseAndVerify($secret, $payload, $signature, $timestamp);

        expect($event->event)->toBe('recording.deleted')
            ->and($event->recordingId())->toBe('rec_1');
    });

    it('throws when the webhook signature is invalid', function () {
        expect(fn () => (new WebhooksResource)->parseAndVerify('secret', '{}', 'nope', '1'))
            ->toThrow(PocketException::class, 'Invalid webhook signature');
    });

    it('throws when the webhook payload is not JSON', function () {
        expect(fn () => (new WebhooksResource)->parse('not-json'))
            ->toThrow(PocketException::class, 'Invalid JSON webhook payload');
    });
});
