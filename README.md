# Domain Providers Laravel

Laravel integration package for `community-sdks/domain-providers-php`.

This package provides the Laravel bridge layer for:
- database persistence of provider instances and discovered TLDs
- encrypted provider credentials/config storage
- DB-driven provider bootstrapping into a singleton runtime handler
- queued per-provider TLD sync jobs
- dispatch command + scheduler integration
- cache invalidation hooks for provider/TLD changes

It intentionally keeps provider/domain logic in the pure PHP package.

## Installation

```bash
composer require community-sdks/domain-providers-laravel
```

If developing in this mono-repo, the package already uses a local path repository to `../php`.

Publish config and migrations:

```bash
php artisan vendor:publish --tag=domain-providers-config
php artisan vendor:publish --tag=domain-providers-migrations
php artisan migrate
```

## Configuration

Config file: `config/domain-providers.php`

Important settings:
- `auto_register_from_database`
- `tenancy.enabled`, `tenancy.column`, `tenancy.current_tenant_id`, `tenancy.include_global_providers`
- `cache.enabled`, `cache.key_prefix`, `cache.ttl_seconds`
- `sync.enabled`
- `sync.dispatch_cadence` (for scheduler cadence method, e.g. `everyFifteenMinutes`)
- `sync.minimum_interval_minutes` (default `1440`)
- `sync.queue_connection`, `sync.queue_name`
- `sync.dispatch_mode` (`queued` or `inline`)
- `sync.mark_missing_tlds_inactive`
- `sync.only_active_providers`
- `sync.only_tld_discovery_providers`
- `sync.register_scheduler`

### Environment Variables

- `DOMAIN_PROVIDERS_AUTO_REGISTER`
- `DOMAIN_PROVIDERS_TENANCY_ENABLED`
- `DOMAIN_PROVIDERS_TENANT_COLUMN`
- `DOMAIN_PROVIDERS_TENANT_ID`
- `DOMAIN_PROVIDERS_INCLUDE_GLOBAL_PROVIDERS`
- `DOMAIN_PROVIDERS_CACHE_ENABLED`
- `DOMAIN_PROVIDERS_CACHE_PREFIX`
- `DOMAIN_PROVIDERS_CACHE_TTL`
- `DOMAIN_PROVIDERS_SYNC_ENABLED`
- `DOMAIN_PROVIDERS_SYNC_DISPATCH_CADENCE`
- `DOMAIN_PROVIDERS_SYNC_MIN_INTERVAL_MINUTES`
- `DOMAIN_PROVIDERS_SYNC_QUEUE_CONNECTION`
- `DOMAIN_PROVIDERS_SYNC_QUEUE_NAME`
- `DOMAIN_PROVIDERS_SYNC_DISPATCH_MODE`
- `DOMAIN_PROVIDERS_SYNC_MARK_MISSING_INACTIVE`
- `DOMAIN_PROVIDERS_SYNC_ONLY_ACTIVE`
- `DOMAIN_PROVIDERS_SYNC_ONLY_DISCOVERY`
- `DOMAIN_PROVIDERS_SYNC_REGISTER_SCHEDULER`

## Database Schema

### `domain_providers`
- `id` UUID primary key
- `tenant_id` nullable string
- `name` string
- `driver` string
- `config` encrypted text (JSON encoded before encryption)
- `rules` nullable JSON (non-secret policy metadata)
- `priority` int
- `is_active` bool
- `last_tld_sync_at`, `last_tld_sync_status`, `last_tld_sync_error`, `last_tld_sync_duration_ms`
- `notes` nullable text
- timestamps

### `domain_provider_tlds`
- `id` UUID primary key
- `tenant_id` nullable string
- `domain_provider_id` UUID FK -> `domain_providers.id`
- `extension` normalized TLD (`com`, `co.uk`, no leading dot)
- `price` nullable decimal placeholder
- `currency` nullable string
- `is_active` bool
- `synced_at` nullable timestamp
- `metadata` nullable JSON
- timestamps
- unique(`domain_provider_id`, `extension`)

## Runtime Integration

The package registers `DomainProviders\Handler\DomainProviderHandler` as a **singleton**.

Build flow:
1. load active providers from DB
2. decrypt+decode config
3. normalize rules
4. resolve provider factory by `driver`
5. instantiate provider + provider config
6. register into handler with DB provider UUID key
7. apply preferred TLD mappings from rules

Container bindings:
- `DomainProviders\Handler\DomainProviderHandler`
- alias to `DomainProviders\Contract\DomainProviderInterface`

## Rules Conventions

`rules` is for non-secret provider policy metadata (JSON), e.g.:

```json
{
  "included_tlds": ["com", "net"],
  "excluded_tlds": ["xyz"],
  "priority_tlds": ["io"],
  "preferred_tlds": ["co.uk"]
}
```

## TLD Sync

Command:

```bash
php artisan domain-providers:sync-tlds-dispatch
```

Options:
- `--provider=<uuid>` sync eligibility for one provider
- `--tenant=<tenant-id>` optional tenant context override
- `--inline` execute sync inline (no queue dispatch)
- `--force` ignore due-time check

Queued job:
- `DomainProviders\Laravel\Jobs\SyncDomainProviderTldsJob`
- one job per provider
- checks active provider state
- updates provider sync status fields

Sync behavior:
- verifies provider supports TLD discovery interface
- normalizes discovered extensions
- upserts `domain_provider_tlds`
- updates `synced_at`
- marks missing known TLD rows inactive if enabled
- does not hard-delete missing rows by default

## Cache Invalidation

`DomainProviderObserver` and `DomainProviderTldObserver` flush package caches on save/delete.

## Testing

```bash
vendor/bin/phpunit
```

Current test coverage includes:
- encrypted config read/write behavior
- active provider resolution order
- singleton handler assembly
- TLD sync normalization/upsert/missing inactive behavior
- dispatch command due-provider selection
