<?php

use Yannelli\Pocket\Facades\Pocket;
use Yannelli\Pocket\Pocket as PocketClass;
use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\SearchResource;
use Yannelli\Pocket\Resources\TagsResource;

it('registers the pocket service', function () {
    expect(app()->bound(PocketClass::class))->toBeTrue();
});

it('can resolve pocket from container', function () {
    $pocket = app(PocketClass::class);

    expect($pocket)->toBeInstanceOf(PocketClass::class);
});

it('can use the facade', function () {
    expect(Pocket::getFacadeRoot())->toBeInstanceOf(PocketClass::class);
});

it('can access recordings via facade', function () {
    expect(Pocket::recordings())->toBeInstanceOf(RecordingsResource::class);
});

it('can access folders via facade', function () {
    expect(Pocket::folders())->toBeInstanceOf(FoldersResource::class);
});

it('can access tags via facade', function () {
    expect(Pocket::tags())->toBeInstanceOf(TagsResource::class);
});

it('can access search via facade', function () {
    expect(Pocket::search())->toBeInstanceOf(SearchResource::class);
});
