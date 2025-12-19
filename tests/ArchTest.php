<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('data objects are readonly')
    ->expect('PocketLabs\Pocket\Data')
    ->toBeReadonly();

arch('exceptions extend base exception')
    ->expect('PocketLabs\Pocket\Exceptions')
    ->toExtend('PocketLabs\Pocket\Exceptions\PocketException')
    ->ignoring('PocketLabs\Pocket\Exceptions\PocketException');

arch('enums are backed by strings')
    ->expect('PocketLabs\Pocket\Enums')
    ->toBeStringBackedEnums();
