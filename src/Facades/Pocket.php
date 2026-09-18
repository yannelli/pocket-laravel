<?php

declare(strict_types=1);

namespace Yannelli\Pocket\Facades;

use Illuminate\Support\Facades\Facade;
use Yannelli\Pocket\PocketClient;
use Yannelli\Pocket\Resources\AudioResource;
use Yannelli\Pocket\Resources\FoldersResource;
use Yannelli\Pocket\Resources\RecordingsResource;
use Yannelli\Pocket\Resources\SearchResource;
use Yannelli\Pocket\Resources\TagsResource;
use Yannelli\Pocket\Resources\UsersResource;
use Yannelli\Pocket\Resources\WebhooksResource;

/**
 * @method static RecordingsResource recordings()
 * @method static FoldersResource folders()
 * @method static TagsResource tags()
 * @method static AudioResource audio(?string $recordingId = null)
 * @method static SearchResource search()
 * @method static UsersResource users()
 * @method static WebhooksResource webhooks()
 * @method static self withApiKey(string $apiKey)
 * @method static PocketClient getClient()
 *
 * @see \Yannelli\Pocket\Pocket
 */
class Pocket extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Yannelli\Pocket\Pocket::class;
    }
}
