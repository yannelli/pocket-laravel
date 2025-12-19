<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Facades;

use Illuminate\Support\Facades\Facade;
use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\TagsResource;

/**
 * @method static RecordingsResource recordings()
 * @method static FoldersResource folders()
 * @method static TagsResource tags()
 *
 * @see \Yannelli\Pocket\Pocket
 */
class Pocket extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Yannelli\Pocket\Pocket::class;
    }
}
