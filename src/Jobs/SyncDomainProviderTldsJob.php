<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Jobs;

use DomainProviders\Laravel\Actions\SyncDomainProviderTldsAction;
use DomainProviders\Laravel\Models\DomainProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncDomainProviderTldsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $providerId,
        public readonly ?string $tenantId = null,
    )
    {
        $this->onConnection(config('domain-providers.sync.queue_connection'));
        $this->onQueue((string) config('domain-providers.sync.queue_name', 'domain-providers'));
    }

    public function handle(SyncDomainProviderTldsAction $action): void
    {
        /** @var DomainProvider|null $provider */
        $provider = DomainProvider::query()
            ->forTenant($this->tenantId)
            ->find($this->providerId);
        if ($provider === null || !$provider->is_active) {
            return;
        }

        $action->sync($provider);
    }
}
