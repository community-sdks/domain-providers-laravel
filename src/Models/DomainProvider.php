<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Models;

use DomainProviders\Laravel\Casts\EncryptedJsonCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DomainProvider extends Model
{
    use HasUuids;

    protected $table = 'domain_providers';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => EncryptedJsonCast::class,
            'rules' => 'array',
            'is_active' => 'boolean',
            'last_tld_sync_at' => 'datetime',
        ];
    }

    public function tlds(): HasMany
    {
        return $this->hasMany(DomainProviderTld::class, 'domain_provider_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        if (!config('domain-providers.tenancy.enabled', false)) {
            return $query;
        }

        if ($tenantId === null || trim($tenantId) === '') {
            return $query;
        }

        $column = (string) config('domain-providers.tenancy.column', 'tenant_id');
        $includeGlobal = (bool) config('domain-providers.tenancy.include_global_providers', true);

        return $query->where(static function (Builder $inner) use ($column, $tenantId, $includeGlobal): void {
            $inner->where($column, $tenantId);

            if ($includeGlobal) {
                $inner->orWhereNull($column);
            }
        });
    }

    /** @return array<string, mixed> */
    public function normalizedConfig(): array
    {
        $config = $this->config;

        return is_array($config) ? $config : [];
    }

    /** @return array<string, mixed> */
    public function normalizedRules(): array
    {
        $rules = $this->rules;

        return is_array($rules) ? $rules : [];
    }
}
