<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Observers;

use DomainProviders\Laravel\Models\DomainProviderTld;
use DomainProviders\Laravel\Services\DomainProviderCacheService;

final class DomainProviderTldObserver
{
    public function __construct(private readonly DomainProviderCacheService $cacheService)
    {
    }

    public function saved(DomainProviderTld $tld): void
    {
        $this->cacheService->flush();
    }

    public function deleted(DomainProviderTld $tld): void
    {
        $this->cacheService->flush();
    }
}
