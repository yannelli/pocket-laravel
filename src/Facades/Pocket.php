<?php

declare(strict_types=1);

namespace PocketLabs\Pocket\Facades;

use Illuminate\Support\Facades\Facade;
use PocketLabs\Pocket\Resources\FoldersResource;
use PocketLabs\Pocket\Resources\RecordingsResource;
use PocketLabs\Pocket\Resources\TagsResource;

/**
 * @method static RecordingsResource recordings()
 * @method static FoldersResource folders()
 * @method static TagsResource tags()
 *
 * @see \PocketLabs\Pocket\Pocket
 */
class Pocket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \PocketLabs\Pocket\Pocket::class;
    }
}
