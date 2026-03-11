<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Services;

use DomainProviders\Laravel\Actions\SyncDomainProviderTldsAction;
use DomainProviders\Laravel\Models\DomainProvider;

final class DomainProviderTldSyncService
{
    public function __construct(private readonly SyncDomainProviderTldsAction $action)
    {
    }

    /**
     * @return array{synced: int, missing_marked_inactive: int, status: string}
     */
    public function syncByProviderId(string $providerId, ?string $tenantId = null): array
    {
        return $this->action->syncByProviderId($providerId, $tenantId);
    }

    /**
     * @return array{synced: int, missing_marked_inactive: int, status: string}
     */
    public function sync(DomainProvider $provider): array
    {
        return $this->action->sync($provider);
    }
}
