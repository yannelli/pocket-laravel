<?php

use Yannelli\Pocket\Pocket;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\AudioResource;
use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\TagsResource;

it('can create a pocket instance', function () {
    $pocket = new Pocket('pk_test_key');

    expect($pocket)->toBeInstanceOf(Pocket::class)
        ->and($pocket->getClient())->toBeInstanceOf(PocketClient::class);
});

it('can create a pocket instance from config', function () {
    $pocket = Pocket::fromConfig([
        'api_key' => 'pk_test_key',
        'base_url' => 'https://app.heypocket.com',
        'api_version' => 'v1',
        'timeout' => 30,
        'retry' => [
            'times' => 3,
            'sleep' => 1000,
        ],
    ]);

    expect($pocket)->toBeInstanceOf(Pocket::class);
});

it('can access recordings resource', function () {
    $pocket = new Pocket('pk_test_key');

    expect($pocket->recordings())->toBeInstanceOf(RecordingsResource::class);
});

it('can access folders resource', function () {
    $pocket = new Pocket('pk_test_key');

    expect($pocket->folders())->toBeInstanceOf(FoldersResource::class);
});

it('can access tags resource', function () {
    $pocket = new Pocket('pk_test_key');

    expect($pocket->tags())->toBeInstanceOf(TagsResource::class);
});

it('can access audio resource', function () {
    $pocket = new Pocket('pk_test_key');

    expect($pocket->audio())->toBeInstanceOf(AudioResource::class);
});

it('returns same resource instance on multiple calls', function () {
    $pocket = new Pocket('pk_test_key');

    $recordings1 = $pocket->recordings();
    $recordings2 = $pocket->recordings();

    expect($recordings1)->toBe($recordings2);
});
