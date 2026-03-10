<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Stubs;

use DomainProviders\Config\ProviderConfig;
use DomainProviders\Contract\DomainProviderInterface;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;

final class FakeFactoryResolver extends LaravelProviderFactoryResolver
{
    /** @param list<string> $extensions */
    public function __construct(private readonly array $extensions = ['com', 'net'])
    {
    }

    /**
     * @return array{0: DomainProviderInterface, 1: ProviderConfig}
     */
    public function buildProvider(string $driver, array $config, array $rules, int $priority): array
    {
        if ($driver !== 'fake') {
            return parent::buildProvider($driver, $config, $rules, $priority);
        }

        $providerConfig = new FakeProviderConfig(
            onlyTlds: null,
            exceptTlds: [],
            priority: $priority,
            priorityTlds: [],
        );

        return [new FakeDiscoveryProvider($this->extensions), $providerConfig];
    }
}
