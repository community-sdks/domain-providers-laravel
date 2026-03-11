<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Providers;

use DomainProviders\Contract\DomainProviderInterface;
use DomainProviders\Handler\DomainProviderHandler;
use DomainProviders\Laravel\Actions\SyncDomainProviderTldsAction;
use DomainProviders\Laravel\Console\Commands\DispatchDomainProviderTldSyncCommand;
use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Models\DomainProviderTld;
use DomainProviders\Laravel\Observers\DomainProviderObserver;
use DomainProviders\Laravel\Observers\DomainProviderTldObserver;
use DomainProviders\Laravel\Services\DomainProviderCacheService;
use DomainProviders\Laravel\Services\DomainProviderRegistryBuilder;
use DomainProviders\Laravel\Services\DomainProviderTldSyncService;
use DomainProviders\Laravel\Services\LaravelProviderFactoryResolver;
use DomainProviders\Laravel\Services\ProviderConfigResolver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

final class DomainProvidersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/domain-providers.php', 'domain-providers');

        $this->app->singleton(DomainProviderCacheService::class);
        $this->app->singleton(ProviderConfigResolver::class);
        $this->app->singleton(LaravelProviderFactoryResolver::class);
        $this->app->singleton(DomainProviderRegistryBuilder::class);
        $this->app->singleton(SyncDomainProviderTldsAction::class);
        $this->app->singleton(DomainProviderTldSyncService::class);

        $this->app->singleton(DomainProviderHandler::class, function ($app): DomainProviderHandler {
            if (!config('domain-providers.auto_register_from_database', true)) {
                return new DomainProviderHandler();
            }

            return $app->make(DomainProviderRegistryBuilder::class)->build();
        });

        $this->app->alias(DomainProviderHandler::class, DomainProviderInterface::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/domain-providers.php' => config_path('domain-providers.php'),
        ], 'domain-providers-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'domain-providers-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        DomainProvider::observe($this->app->make(DomainProviderObserver::class));
        DomainProviderTld::observe($this->app->make(DomainProviderTldObserver::class));

        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchDomainProviderTldSyncCommand::class,
            ]);
        }

        $this->registerSchedulerHook();
    }

    private function registerSchedulerHook(): void
    {
        if (!config('domain-providers.sync.enabled', true) || !config('domain-providers.sync.register_scheduler', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $event = $schedule->command('domain-providers:sync-tlds-dispatch');
            $cadence = (string) config('domain-providers.sync.dispatch_cadence', 'everyFifteenMinutes');

            if (method_exists($event, $cadence)) {
                $event->{$cadence}();
                return;
            }

            $event->everyFifteenMinutes();
        });
    }
}
