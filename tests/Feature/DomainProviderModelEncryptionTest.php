<?php

declare(strict_types=1);

namespace DomainProviders\Laravel\Tests\Feature;

use DomainProviders\Laravel\Models\DomainProvider;
use DomainProviders\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\DB;

final class DomainProviderModelEncryptionTest extends TestCase
{
    public function test_config_is_encrypted_in_database_and_decrypted_in_model(): void
    {
        $provider = DomainProvider::query()->create([
            'name' => 'Fake Provider',
            'driver' => 'fake',
            'config' => [
                'api_key' => 'secret-key',
                'api_secret' => 'secret-value',
            ],
            'rules' => ['included_tlds' => ['com']],
            'priority' => 5,
            'is_active' => true,
        ]);

        $raw = DB::table('domain_providers')->where('id', $provider->getKey())->value('config');
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('secret-key', $raw);

        $fresh = DomainProvider::query()->findOrFail($provider->getKey());
        $this->assertSame('secret-key', $fresh->config['api_key']);
        $this->assertSame(['included_tlds' => ['com']], $fresh->rules);
    }
}
