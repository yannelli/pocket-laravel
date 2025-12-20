<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Facades;

use Illuminate\Support\Facades\Facade;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\TagsResource;

/**
 * @method static RecordingsResource recordings()
 * @method static FoldersResource folders()
 * @method static TagsResource tags()
 * @method static PocketClient getClient()
 *
 * @see \Yannelli\Pocket\Pocket
 */
class Pocket extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Yannelli\Pocket\Pocket::class;
    }
}
