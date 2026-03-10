<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Feature;

use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Services\ProviderConfigResolver;
use DomainProviders\Laravel\Tests\TestCase;

final class ProviderConfigResolverTest extends TestCase
{
    public function test_resolves_only_active_providers_ordered_by_priority(): void
    {
        DomainProvider::query()->create([
            'name' => 'Inactive',
            'driver' => 'fake',
            'config' => ['token' => 'x'],
            'rules' => null,
            'priority' => 1,
            'is_active' => false,
        ]);

        DomainProvider::query()->create([
            'name' => 'Second',
            'driver' => 'fake',
            'config' => ['token' => 'x2'],
            'rules' => ['priority_tlds' => ['net']],
            'priority' => 20,
            'is_active' => true,
        ]);

        DomainProvider::query()->create([
            'name' => 'First',
            'driver' => 'fake',
            'config' => ['token' => 'x3'],
            'rules' => ['priority_tlds' => ['com']],
            'priority' => 10,
            'is_active' => true,
        ]);

        $definitions = $this->app->make(ProviderConfigResolver::class)->resolveActiveProviderDefinitions();

        $this->assertCount(2, $definitions);
        $this->assertSame('First', $definitions[0]['name']);
        $this->assertSame('Second', $definitions[1]['name']);
    }

    public function test_resolves_active_providers_for_specific_tenant_with_global_fallback(): void
    {
        config()->set('domain-providers.tenancy.enabled', true);
        config()->set('domain-providers.tenancy.include_global_providers', true);

        DomainProvider::query()->create([
            'tenant_id' => 'tenant-a',
            'name' => 'Tenant A Provider',
            'driver' => 'fake',
            'config' => ['token' => 'a'],
            'rules' => null,
            'priority' => 10,
            'is_active' => true,
        ]);

        DomainProvider::query()->create([
            'tenant_id' => 'tenant-b',
            'name' => 'Tenant B Provider',
            'driver' => 'fake',
            'config' => ['token' => 'b'],
            'rules' => null,
            'priority' => 20,
            'is_active' => true,
        ]);

        DomainProvider::query()->create([
            'tenant_id' => null,
            'name' => 'Global Provider',
            'driver' => 'fake',
            'config' => ['token' => 'g'],
            'rules' => null,
            'priority' => 30,
            'is_active' => true,
        ]);

        $definitions = $this->app->make(ProviderConfigResolver::class)
            ->resolveActiveProviderDefinitions('tenant-a');

        $this->assertCount(2, $definitions);
        $this->assertSame('Tenant A Provider', $definitions[0]['name']);
        $this->assertSame('Global Provider', $definitions[1]['name']);
    }
}
