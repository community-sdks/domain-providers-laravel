<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Actions;

use DomainProviders\Contract\TldDiscoveryInterface;
use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Models\DomainProviderTld;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use DomainProviders\Laravel\Support\TldNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SyncDomainProviderTldsAction
{
    public function __construct(private readonly LaravelProviderFactoryResolver $factoryResolver)
    {
    }

    /**
     * @return array{synced: int, missing_marked_inactive: int, status: string}
     */
    public function syncByProviderId(string $providerId, ?string $tenantId = null): array
    {
        /** @var DomainProvider|null $provider */
        $provider = DomainProvider::query()
            ->forTenant($tenantId)
            ->find($providerId);

        if ($provider === null) {
            return ['synced' => 0, 'missing_marked_inactive' => 0, 'status' => 'skipped'];
        }

        return $this->sync($provider);
    }

    /**
     * @return array{synced: int, missing_marked_inactive: int, status: string}
     */
    public function sync(DomainProvider $provider): array
    {
        $startedAt = microtime(true);
        $now = Carbon::now();

        if (!$provider->is_active) {
            $this->markSyncState($provider, 'skipped', null, $startedAt);
            return ['synced' => 0, 'missing_marked_inactive' => 0, 'status' => 'skipped'];
        }

        try {
            [$instance] = $this->factoryResolver->buildProvider(
                (string) $provider->driver,
                $provider->normalizedConfig(),
                $provider->normalizedRules(),
                (int) $provider->priority,
            );

            if (!$instance instanceof TldDiscoveryInterface) {
                $this->markSyncState($provider, 'skipped', null, $startedAt);
                return ['synced' => 0, 'missing_marked_inactive' => 0, 'status' => 'skipped'];
            }

            $extensions = [];
            foreach ($instance->listSupportedTlds() as $extension) {
                $normalized = TldNormalizer::normalize($extension);
                if ($normalized === '') {
                    continue;
                }

                $extensions[$normalized] = true;
            }

            $syncedCount = 0;
            DB::transaction(function () use ($provider, $extensions, $now, &$syncedCount): void {
                foreach (array_keys($extensions) as $extension) {
                    /** @var DomainProviderTld $model */
                    $model = DomainProviderTld::query()->firstOrNew([
                        'domain_provider_id' => $provider->getKey(),
                        'extension' => $extension,
                    ]);

                    if (!$model->exists) {
                        $model->id = (string) str()->uuid();
                    }

                    $model->tenant_id = $provider->tenant_id;
                    $model->is_active = true;
                    $model->synced_at = $now;
                    $model->save();
                    $syncedCount++;
                }
            });

            $missingMarkedInactive = 0;
            if (config('domain-providers.sync.mark_missing_tlds_inactive', true)) {
                $missingMarkedInactive = DomainProviderTld::query()
                    ->where('domain_provider_id', $provider->getKey())
                    ->forTenant($provider->tenant_id)
                    ->whereNotIn('extension', array_keys($extensions))
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'updated_at' => $now]);
            }

            $this->markSyncState($provider, 'success', null, $startedAt);

            return [
                'synced' => $syncedCount,
                'missing_marked_inactive' => $missingMarkedInactive,
                'status' => 'success',
            ];
        } catch (\Throwable $exception) {
            $this->markSyncState($provider, 'failed', $exception->getMessage(), $startedAt);
            throw $exception;
        }
    }

    private function markSyncState(DomainProvider $provider, string $status, ?string $error, float $startedAt): void
    {
        $provider->forceFill([
            'last_tld_sync_at' => Carbon::now(),
            'last_tld_sync_status' => $status,
            'last_tld_sync_error' => $error,
            'last_tld_sync_duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ])->save();
    }
}
