<?php

declare(strict_types=1);

namespace Yannelli\Pocket;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PocketServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('pocket')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Pocket::class, function ($app) {
            $config = $app['config']->get('pocket', []);

            if (empty($config['api_key'])) {
                throw new \InvalidArgumentException(
                    'Pocket API key is not configured. Please set POCKET_API_KEY in your .env file.'
                );
            }

            return Pocket::fromConfig($config);
        });

        $this->app->alias(Pocket::class, 'pocket');
    }
}
