<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Observers;

use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Services\DomainProviderCacheService;

final class DomainProviderObserver
{
    public function __construct(private readonly DomainProviderCacheService $cacheService)
    {
    }

    public function saved(DomainProvider $provider): void
    {
        $this->cacheService->flush();
    }

    public function deleted(DomainProvider $provider): void
    {
        $this->cacheService->flush();
    }
}
