<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

final class DomainProviderCacheService
{
    private const ACTIVE_PROVIDER_DEFINITIONS_KEY = 'active_provider_definitions';

    /**
     * @param Closure(): list<array<string, mixed>> $resolver
     * @return list<array<string, mixed>>
     */
    public function rememberActiveProviderDefinitions(Closure $resolver, ?string $tenantId = null): array
    {
        if (!config('domain-providers.cache.enabled', true)) {
            return $resolver();
        }

        $key = $this->key(self::ACTIVE_PROVIDER_DEFINITIONS_KEY, $tenantId);
        $ttl = (int) config('domain-providers.cache.ttl_seconds', 300);

        $value = Cache::remember($key, $ttl, $resolver);

        return is_array($value) ? $value : [];
    }

    public function flush(?string $tenantId = null): void
    {
        if ($tenantId !== null && trim($tenantId) !== '') {
            Cache::forget($this->key(self::ACTIVE_PROVIDER_DEFINITIONS_KEY, $tenantId));
        }

        Cache::forget($this->key(self::ACTIVE_PROVIDER_DEFINITIONS_KEY, null));
    }

    private function key(string $suffix, ?string $tenantId): string
    {
        $prefix = (string) config('domain-providers.cache.key_prefix', 'domain_providers');

        if (!config('domain-providers.tenancy.enabled', false) || $tenantId === null || trim($tenantId) === '') {
            return $prefix . ':' . $suffix;
        }

        return $prefix . ':' . $suffix . ':tenant:' . $tenantId;
    }
}
