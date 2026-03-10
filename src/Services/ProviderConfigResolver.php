<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Services;

use DomainProviders\Laravel\Models\DomainProvider;
use Illuminate\Support\Collection;

final class ProviderConfigResolver
{
    public function __construct(private readonly DomainProviderCacheService $cacheService)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveActiveProviderDefinitions(?string $tenantId = null): array
    {
        return $this->cacheService->rememberActiveProviderDefinitions(function () use ($tenantId): array {
            /** @var Collection<int, DomainProvider> $providers */
            $providers = DomainProvider::query()
                ->active()
                ->forTenant($tenantId)
                ->orderedByPriority()
                ->get();

            return $providers->map(static function (DomainProvider $provider): array {
                return [
                    'id' => $provider->getKey(),
                    'tenant_id' => $provider->tenant_id,
                    'name' => $provider->name,
                    'driver' => strtolower(trim((string) $provider->driver)),
                    'priority' => (int) $provider->priority,
                    'config' => $provider->normalizedConfig(),
                    'rules' => $provider->normalizedRules(),
                ];
            })->all();
        }, $tenantId);
    }
}
