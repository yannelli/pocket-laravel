<?php

declare(strict_types=1);

namespace Yannelli\Pocket;

use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PocketServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('pocket')
            ->hasConfigFile();
    }

    /**
     * Register the Pocket singleton and alias.
     */
    public function packageRegistered(): void
    {
        $this->app->singleton(Pocket::class, function (Application $app): Pocket {
            /** @var array{api_key?: string, base_url?: string, api_version?: string, timeout?: int, retry?: array{times?: int, sleep?: int}} $config */
            $config = $app['config']->get('pocket', []);

            if (empty($config['api_key'])) {
                throw new InvalidArgumentException(
                    'Pocket API key is not configured. Please set POCKET_API_KEY in your .env file.'
                );
            }

            return Pocket::fromConfig($config);
        });

        $this->app->alias(Pocket::class, 'pocket');
    }
}
