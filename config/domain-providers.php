<?php

declare(strict_types=1);

return [
    'auto_register_from_database' => env('DOMAIN_PROVIDERS_AUTO_REGISTER', true),

    'tenancy' => [
        'enabled' => env('DOMAIN_PROVIDERS_TENANCY_ENABLED', false),
        'column' => env('DOMAIN_PROVIDERS_TENANT_COLUMN', 'tenant_id'),
        'current_tenant_id' => env('DOMAIN_PROVIDERS_TENANT_ID'),
        'include_global_providers' => env('DOMAIN_PROVIDERS_INCLUDE_GLOBAL_PROVIDERS', true),
    ],

    'cache' => [
        'enabled' => env('DOMAIN_PROVIDERS_CACHE_ENABLED', true),
        'key_prefix' => env('DOMAIN_PROVIDERS_CACHE_PREFIX', 'domain_providers'),
        'ttl_seconds' => (int) env('DOMAIN_PROVIDERS_CACHE_TTL', 300),
    ],

    'sync' => [
        'enabled' => env('DOMAIN_PROVIDERS_SYNC_ENABLED', true),
        'dispatch_cadence' => env('DOMAIN_PROVIDERS_SYNC_DISPATCH_CADENCE', 'everyFifteenMinutes'),
        'minimum_interval_minutes' => (int) env('DOMAIN_PROVIDERS_SYNC_MIN_INTERVAL_MINUTES', 1440),
        'queue_connection' => env('DOMAIN_PROVIDERS_SYNC_QUEUE_CONNECTION'),
        'queue_name' => env('DOMAIN_PROVIDERS_SYNC_QUEUE_NAME', 'domain-providers'),
        'dispatch_mode' => env('DOMAIN_PROVIDERS_SYNC_DISPATCH_MODE', 'queued'),
        'mark_missing_tlds_inactive' => env('DOMAIN_PROVIDERS_SYNC_MARK_MISSING_INACTIVE', true),
        'only_active_providers' => env('DOMAIN_PROVIDERS_SYNC_ONLY_ACTIVE', true),
        'only_tld_discovery_providers' => env('DOMAIN_PROVIDERS_SYNC_ONLY_DISCOVERY', true),
        'register_scheduler' => env('DOMAIN_PROVIDERS_SYNC_REGISTER_SCHEDULER', true),
    ],

    'models' => [
        'provider' => DomainProviders\Laravel\Models\DomainProvider::class,
        'provider_tld' => DomainProviders\Laravel\Models\DomainProviderTld::class,
    ],
];
