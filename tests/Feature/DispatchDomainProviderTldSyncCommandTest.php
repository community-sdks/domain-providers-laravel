<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Feature;

use DomainProviders\Laravel\Jobs\SyncDomainProviderTldsJob;
use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use DomainProviders\Laravel\Tests\Stubs\FakeFactoryResolver;
use DomainProviders\Laravel\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

final class DispatchDomainProviderTldSyncCommandTest extends TestCase
{
    public function test_dispatches_jobs_only_for_due_providers(): void
    {
        config()->set('domain-providers.sync.minimum_interval_minutes', 60);
        Bus::fake();

        $this->app->singleton(LaravelProviderFactoryResolver::class, static fn () => new FakeFactoryResolver());

        $due = DomainProvider::query()->create([
            'name' => 'Due',
            'driver' => 'fake',
            'config' => ['token' => 'x'],
            'rules' => null,
            'priority' => 10,
            'is_active' => true,
            'last_tld_sync_at' => Carbon::now()->subHours(2),
        ]);

        DomainProvider::query()->create([
            'name' => 'Not Due',
            'driver' => 'fake',
            'config' => ['token' => 'y'],
            'rules' => null,
            'priority' => 11,
            'is_active' => true,
            'last_tld_sync_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('domain-providers:sync-tlds-dispatch')
            ->assertExitCode(0);

        Bus::assertDispatched(SyncDomainProviderTldsJob::class, static function (SyncDomainProviderTldsJob $job) use ($due): bool {
            return $job->providerId === (string) $due->getKey();
        });

        Bus::assertDispatchedTimes(SyncDomainProviderTldsJob::class, 1);
    }
}
