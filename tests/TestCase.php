<?php

namespace Yannelli\Pocket\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yannelli\Pocket\PocketServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            PocketServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('pocket.api_key', 'pk_test_key');
        config()->set('pocket.base_url', 'https://public.heypocketai.com');
        config()->set('pocket.api_version', 'v1');
        config()->set('pocket.timeout', 30);
        config()->set('pocket.retry.times', 3);
        config()->set('pocket.retry.sleep', 1000);
    }
}
