<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Feature;

use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Models\DomainProviderTld;
use DomainProviders\Laravel\Services\DomainProviderTldSyncService;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use DomainProviders\Laravel\Tests\Stubs\FakeFactoryResolver;
use DomainProviders\Laravel\Tests\TestCase;

final class DomainProviderTldSyncServiceTest extends TestCase
{
    public function test_sync_normalizes_extensions_upserts_and_marks_missing_inactive(): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'Sync Provider',
            'driver' => 'fake',
            'config' => ['token' => 'x'],
            'rules' => null,
            'priority' => 10,
            'is_active' => true,
        ]);

        DomainProviderTld::query()->create([
            'id' => (string) str()->uuid(),
            'domain_provider_id' => $provider->getKey(),
            'extension' => 'org',
            'is_active' => true,
        ]);

        $this->app->singleton(LaravelProviderFactoryResolver::class, static fn () => new FakeFactoryResolver(['.COM', ' co.uk ']));

        $result = $this->app->make(DomainProviderTldSyncService::class)->sync($provider->fresh());

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('domain_provider_tlds', [
            'domain_provider_id' => $provider->getKey(),
            'extension' => 'com',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('domain_provider_tlds', [
            'domain_provider_id' => $provider->getKey(),
            'extension' => 'co.uk',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('domain_provider_tlds', [
            'domain_provider_id' => $provider->getKey(),
            'extension' => 'org',
            'is_active' => false,
        ]);

        $provider->refresh();
        $this->assertSame('success', $provider->last_tld_sync_status);
        $this->assertNotNull($provider->last_tld_sync_at);
    }
}
