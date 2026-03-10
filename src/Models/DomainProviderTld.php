<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Models;

use DomainProviders\Laravel\Support\TldNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainProviderTld extends Model
{
    use HasUuids;

    protected $table = 'domain_provider_tlds';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $model): void {
            $model->extension = TldNormalizer::normalize((string) $model->extension);
        });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class, 'domain_provider_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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

        return $query->where($column, $tenantId);
    }
}
