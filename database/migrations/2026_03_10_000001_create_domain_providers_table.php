<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('domain_providers', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable();
            $table->string('name');
            $table->string('driver');
            $table->text('config');
            $table->json('rules')->nullable();
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tld_sync_at')->nullable();
            $table->string('last_tld_sync_status')->nullable();
            $table->text('last_tld_sync_error')->nullable();
            $table->unsignedInteger('last_tld_sync_duration_ms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->index('driver');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_providers');
    }
};
