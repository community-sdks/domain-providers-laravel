<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Feature;

use DomainProviders\Handler\DomainProviderHandler;
use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use DomainProviders\Laravel\Tests\Stubs\FakeFactoryResolver;
use DomainProviders\Laravel\Tests\TestCase;

final class HandlerSingletonAssemblyTest extends TestCase
{
    public function test_handler_is_bound_as_singleton_and_registers_active_provider(): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'Resolver Fake',
            'driver' => 'fake',
            'config' => ['token' => 'x'],
            'rules' => ['preferred_tlds' => ['.com']],
            'priority' => 10,
            'is_active' => true,
        ]);

        $this->app->singleton(LaravelProviderFactoryResolver::class, static fn () => new FakeFactoryResolver());

        $handlerA = $this->app->make(DomainProviderHandler::class);
        $handlerB = $this->app->make(DomainProviderHandler::class);

        $this->assertSame($handlerA, $handlerB);
        $this->assertContains((string) $provider->getKey(), $handlerA->registeredProviderKeys());
    }
}
