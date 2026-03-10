<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Services;

use DomainProviders\Handler\DomainProviderHandler;
use DomainProviders\Laravel\Support\TldNormalizer;

final class DomainProviderRegistryBuilder
{
    public function __construct(
        private readonly ProviderConfigResolver $configResolver,
        private readonly LaravelProviderFactoryResolver $factoryResolver,
    ) {
    }

    public function build(?string $tenantId = null): DomainProviderHandler
    {
        $handler = new DomainProviderHandler();

        if ($tenantId === null && config('domain-providers.tenancy.enabled', false)) {
            $resolved = config('domain-providers.tenancy.current_tenant_id');
            $tenantId = is_scalar($resolved) ? (string) $resolved : null;
        }

        foreach ($this->configResolver->resolveActiveProviderDefinitions($tenantId) as $definition) {
            $providerKey = (string) $definition['id'];
            $driver = (string) $definition['driver'];
            $priority = (int) ($definition['priority'] ?? 100);
            $config = is_array($definition['config'] ?? null) ? $definition['config'] : [];
            $rules = is_array($definition['rules'] ?? null) ? $definition['rules'] : [];

            [$provider, $providerConfig] = $this->factoryResolver->buildProvider($driver, $config, $rules, $priority);
            $handler->registerProvider($providerKey, $provider, $providerConfig);

            foreach ($this->preferredTldMappings($rules) as $tld) {
                $handler->preferProviderForTld($tld, $providerKey);
            }
        }

        return $handler;
    }

    /** @return list<string> */
    private function preferredTldMappings(array $rules): array
    {
        $map = $rules['preferred_tlds'] ?? [];
        if (!is_array($map)) {
            return [];
        }

        $normalized = [];
        foreach ($map as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $item = TldNormalizer::normalize((string) $value);
            if ($item === '') {
                continue;
            }

            $normalized[$item] = true;
        }

        return array_keys($normalized);
    }
}
