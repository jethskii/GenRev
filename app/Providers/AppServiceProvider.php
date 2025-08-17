<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\InventoryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind InventoryService as a singleton so it reuses the same instance
        $this->app->singleton(InventoryService::class, function ($app) {
            return new InventoryService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
