<?php

use Yannelli\Pocket\Data\AudioUrl;

it('can create an AudioUrl from array', function () {
    $data = [
        'signed_url' => 'https://example.com/audio.mp3?signature=abc123',
        'expires_in' => 3600,
        'expires_at' => '2025-12-25T22:52:25.185Z',
    ];

    $audioUrl = AudioUrl::fromArray($data);

    expect($audioUrl)->toBeInstanceOf(AudioUrl::class)
        ->and($audioUrl->signedUrl)->toBe('https://example.com/audio.mp3?signature=abc123')
        ->and($audioUrl->expiresIn)->toBe(3600)
        ->and($audioUrl->expiresAt->format('Y-m-d'))->toBe('2025-12-25');
});

it('can convert to array', function () {
    $data = [
        'signed_url' => 'https://example.com/audio.mp3?signature=abc123',
        'expires_in' => 3600,
        'expires_at' => '2025-12-25T22:52:25.000Z',
    ];

    $audioUrl = AudioUrl::fromArray($data);
    $array = $audioUrl->toArray();

    expect($array)->toHaveKey('signed_url')
        ->and($array['signed_url'])->toBe('https://example.com/audio.mp3?signature=abc123')
        ->and($array['expires_in'])->toBe(3600)
        ->and($array['expires_at'])->toContain('2025-12-25');
});

it('can serialize to json', function () {
    $data = [
        'signed_url' => 'https://example.com/audio.mp3?signature=abc123',
        'expires_in' => 3600,
        'expires_at' => '2025-12-25T22:52:25.000Z',
    ];

    $audioUrl = AudioUrl::fromArray($data);
    $json = json_encode($audioUrl);

    expect($json)->toContain('signed_url')
        ->and($json)->toContain('example.com')
        ->and($json)->toContain('audio.mp3');
});

it('correctly detects expired URLs', function () {
    $pastDate = [
        'signed_url' => 'https://example.com/audio.mp3',
        'expires_in' => 3600,
        'expires_at' => '2020-01-01T00:00:00.000Z',
    ];

    $audioUrl = AudioUrl::fromArray($pastDate);

    expect($audioUrl->isExpired())->toBeTrue()
        ->and($audioUrl->secondsUntilExpiry())->toBe(0);
});

it('correctly detects non-expired URLs', function () {
    $futureDate = (new DateTimeImmutable)->modify('+1 hour')->format('c');
    $data = [
        'signed_url' => 'https://example.com/audio.mp3',
        'expires_in' => 3600,
        'expires_at' => $futureDate,
    ];

    $audioUrl = AudioUrl::fromArray($data);

    expect($audioUrl->isExpired())->toBeFalse()
        ->and($audioUrl->secondsUntilExpiry())->toBeGreaterThan(3500);
});
