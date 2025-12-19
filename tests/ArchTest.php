<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('data objects are readonly')
    ->expect('Yannelli\Pocket\Data')
    ->toBeReadonly();

arch('exceptions extend base exception')
    ->expect('Yannelli\Pocket\Exceptions')
    ->toExtend('Yannelli\Pocket\Exceptions\PocketException')
    ->ignoring('Yannelli\Pocket\Exceptions\PocketException');

arch('enums are backed by strings')
    ->expect('Yannelli\Pocket\Enums')
    ->toBeStringBackedEnums();
