<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Adds support for a Postgres `libpq_options` config key (Neon SNI workaround
        // for older libpq clients - see App\Database\Connectors\NeonPostgresConnector).
        // A no-op for any connection that doesn't set config('database.connections.pgsql.libpq_options').
        $this->app->bind('db.connector.pgsql', fn () => new NeonPostgresConnector);

        // Bind once, reused everywhere (models, controllers, jobs).
        $this->app->singleton(InventoryService::class, fn () => new InventoryService);

        // Optional alias: app('inventory') will resolve the same instance.
        $this->app->alias(InventoryService::class, 'inventory');

        // Tiny health check: confirms the service is actually being resolved.
        // (One log line when the container first builds InventoryService)
        $this->app->resolving(InventoryService::class, function ($svc) {
            static $logged = false;
            if (! $logged && ! app()->runningInConsole()) {
                Log::debug('[inventory] InventoryService resolved and ready.');
                $logged = true;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Place boot-time tweaks here if needed (e.g., URL::forceScheme).
    }
}
