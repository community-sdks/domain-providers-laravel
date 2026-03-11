<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Services;

use DomainProviders\Config\ProviderConfig;
use DomainProviders\Contract\DomainProviderInterface;
use DomainProviders\Provider\GoDaddy\GoDaddyConfig;
use DomainProviders\Provider\GoDaddy\GoDaddyProviderFactory;
use DomainProviders\Laravel\Exceptions\UnsupportedProviderDriverException;

class LaravelProviderFactoryResolver
{
    /**
     * @return array{0: DomainProviderInterface, 1: ProviderConfig}
     */
    public function buildProvider(string $driver, array $config, array $rules, int $priority): array
    {
        return match (strtolower(trim($driver))) {
            'godaddy' => $this->buildGoDaddyProvider($config, $rules, $priority),
            default => throw UnsupportedProviderDriverException::forDriver($driver),
        };
    }

    /**
     * @return array{0: DomainProviderInterface, 1: ProviderConfig}
     */
    private function buildGoDaddyProvider(array $config, array $rules, int $priority): array
    {
        $providerConfig = new GoDaddyConfig(
            apiKey: (string) ($config['api_key'] ?? ''),
            apiSecret: (string) ($config['api_secret'] ?? ''),
            customerId: (string) ($config['customer_id'] ?? ''),
            environment: (string) ($config['environment'] ?? 'production'),
            onlyTlds: $this->listFromRules($rules, 'included_tlds'),
            exceptTlds: $this->listFromRules($rules, 'excluded_tlds') ?? [],
            priority: $priority,
            priorityTlds: $this->listFromRules($rules, 'priority_tlds') ?? [],
        );

        return [GoDaddyProviderFactory::fromConfig($providerConfig), $providerConfig];
    }

    /** @return list<string>|null */
    private function listFromRules(array $rules, string $key): ?array
    {
        if (!isset($rules[$key]) || !is_array($rules[$key])) {
            return null;
        }

        $values = [];
        foreach ($rules[$key] as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $values[] = (string) $value;
        }

        return $values;
    }
}
