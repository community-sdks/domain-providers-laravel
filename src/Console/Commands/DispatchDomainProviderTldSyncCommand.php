<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Console\Commands;

use DomainProviders\Contract\TldDiscoveryInterface;
use DomainProviders\Laravel\Jobs\SyncDomainProviderTldsJob;
use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Services\DomainProviderTldSyncService;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class DispatchDomainProviderTldSyncCommand extends Command
{
    protected $signature = 'domain-providers:sync-tlds-dispatch {--provider=} {--tenant=} {--inline} {--force}';

    protected $description = 'Dispatch one TLD sync job per eligible provider.';

    public function handle(
        LaravelProviderFactoryResolver $factoryResolver,
        DomainProviderTldSyncService $syncService,
    ): int {
        if (!config('domain-providers.sync.enabled', true)) {
            $this->warn('Domain provider sync is disabled by configuration.');
            return self::SUCCESS;
        }

        $providerId = $this->option('provider');
        $tenantId = $this->resolveTenantId();
        $force = (bool) $this->option('force');
        $inline = (bool) $this->option('inline') || config('domain-providers.sync.dispatch_mode') === 'inline';

        $query = DomainProvider::query()->forTenant($tenantId);
        if ($providerId !== null) {
            $query->whereKey((string) $providerId);
        }

        if (config('domain-providers.sync.only_active_providers', true)) {
            $query->where('is_active', true);
        }

        $providers = $query->orderedByPriority()->get();

        $minimumMinutes = (int) config('domain-providers.sync.minimum_interval_minutes', 1440);
        $eligible = 0;

        foreach ($providers as $provider) {
            if (!$force && !$this->isDue($provider, $minimumMinutes)) {
                continue;
            }

            if (config('domain-providers.sync.only_tld_discovery_providers', true) && !$this->supportsDiscovery($provider, $factoryResolver)) {
                continue;
            }

            $eligible++;
            if ($inline) {
                $syncService->sync($provider);
                continue;
            }

            SyncDomainProviderTldsJob::dispatch((string) $provider->getKey(), $tenantId);
        }

        $this->info(sprintf('Processed %d provider(s) for TLD sync dispatch.', $eligible));

        return self::SUCCESS;
    }

    private function isDue(DomainProvider $provider, int $minimumMinutes): bool
    {
        if ($provider->last_tld_sync_at === null) {
            return true;
        }

        return $provider->last_tld_sync_at->diffInMinutes(Carbon::now()) >= $minimumMinutes;
    }

    private function supportsDiscovery(DomainProvider $provider, LaravelProviderFactoryResolver $resolver): bool
    {
        try {
            [$instance] = $resolver->buildProvider(
                (string) $provider->driver,
                $provider->normalizedConfig(),
                $provider->normalizedRules(),
                (int) $provider->priority,
            );

            return $instance instanceof TldDiscoveryInterface;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveTenantId(): ?string
    {
        $option = $this->option('tenant');
        if (is_string($option) && trim($option) !== '') {
            return trim($option);
        }

        if (!config('domain-providers.tenancy.enabled', false)) {
            return null;
        }

        $configured = config('domain-providers.tenancy.current_tenant_id');

        return is_scalar($configured) && trim((string) $configured) !== '' ? trim((string) $configured) : null;
    }
}
