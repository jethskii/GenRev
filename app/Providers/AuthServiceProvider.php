<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Production;
use App\Models\Sale;
use App\Policies\ProductionPolicy;
use App\Policies\SalePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Production::class => ProductionPolicy::class,
        Sale::class       => SalePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
