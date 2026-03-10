<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests;

use DomainProviders\Laravel\Providers\DomainProvidersServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DomainProvidersServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:uQbN3Q9E2aM+G/J8x5JPE0jU5m8hQEMQ4WOdZgEUySo=');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--database' => 'testing']);
    }
}
