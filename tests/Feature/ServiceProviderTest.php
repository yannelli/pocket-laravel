<?php

use PocketLabs\Pocket\Facades\Pocket;
use PocketLabs\Pocket\Pocket as PocketClass;

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
    expect(Pocket::recordings())->toBeInstanceOf(\PocketLabs\Pocket\Resources\RecordingsResource::class);
});

it('can access folders via facade', function () {
    expect(Pocket::folders())->toBeInstanceOf(\PocketLabs\Pocket\Resources\FoldersResource::class);
});

it('can access tags via facade', function () {
    expect(Pocket::tags())->toBeInstanceOf(\PocketLabs\Pocket\Resources\TagsResource::class);
});
