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
     * @return class-string<ProviderConfig>
     */
    public function configClassForDriver(string $driver): string
    {
        return match (strtolower(trim($driver))) {
            'godaddy' => GoDaddyConfig::class,
            default => throw UnsupportedProviderDriverException::forDriver($driver),
        };
    }

    /**
     * Build a provider config template from the config constructor signature.
     *
     * @return array<string, mixed>
     */
    public function configTemplateForDriver(string $driver, bool $snakeCaseKeys = false): array
    {
        $class = $this->configClassForDriver($driver);
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $template = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $key = $snakeCaseKeys ? $this->toSnakeCase($name) : $name;

            if ($parameter->isDefaultValueAvailable()) {
                $template[$key] = $parameter->getDefaultValue();
                continue;
            }

            $template[$key] = $this->emptyValueForParameter($parameter);
        }

        return $template;
    }

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

    private function toSnakeCase(string $value): string
    {
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);
        return strtolower((string) $snake);
    }

    private function emptyValueForParameter(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        if ($type->allowsNull()) {
            return null;
        }

        return match ($type->getName()) {
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            'array' => [],
            default => null,
        };
    }
}
