<?php

use PocketLabs\Pocket\Exceptions\AuthenticationException;
use PocketLabs\Pocket\Exceptions\NotFoundException;
use PocketLabs\Pocket\Exceptions\PocketException;
use PocketLabs\Pocket\Exceptions\RateLimitException;
use PocketLabs\Pocket\Exceptions\ServerException;
use PocketLabs\Pocket\Exceptions\ValidationException;

it('can create pocket exception with details', function () {
    $exception = new PocketException('Error message', 400, ['field' => 'Invalid']);

    expect($exception->getMessage())->toBe('Error message')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getDetails())->toBe(['field' => 'Invalid']);
});

it('can create pocket exception from response', function () {
    $exception = PocketException::fromResponse([
        'error' => 'Something went wrong',
        'details' => ['foo' => 'bar'],
    ], 400);

    expect($exception->getMessage())->toBe('Something went wrong')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getDetails())->toBe(['foo' => 'bar']);
});

it('can create authentication exception', function () {
    $exception = new AuthenticationException;

    expect($exception->getMessage())->toBe('Invalid API key')
        ->and($exception->getCode())->toBe(401);
});

it('can create not found exception', function () {
    $exception = new NotFoundException;

    expect($exception->getMessage())->toBe('Resource not found')
        ->and($exception->getCode())->toBe(404);
});

it('can create rate limit exception', function () {
    $exception = new RateLimitException('Rate limited', 60);

    expect($exception->getMessage())->toBe('Rate limited')
        ->and($exception->getCode())->toBe(429)
        ->and($exception->getRetryAfter())->toBe(60);
});

it('can create validation exception', function () {
    $exception = new ValidationException('Validation failed', [
        'field' => ['Required'],
    ]);

    expect($exception->getMessage())->toBe('Validation failed')
        ->and($exception->getCode())->toBe(400)
        ->and($exception->getDetails())->toBe(['field' => ['Required']]);
});

it('can create server exception', function () {
    $exception = new ServerException;

    expect($exception->getMessage())->toBe('Internal server error')
        ->and($exception->getCode())->toBe(500);
});
