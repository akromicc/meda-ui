<?php

namespace Meda\DynamicCrud;

use Illuminate\Support\ServiceProvider;
use Meda\DynamicCrud\Services\QuerySortingService;
use Meda\DynamicCrud\Services\DynamicCrudService;
use Meda\DynamicCrud\Traits\HasMetadata;

class DynamicCrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(QuerySortingService::class);
        $this->app->singleton(DynamicCrudService::class);
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/dynamic-crud.php', 'dynamic-crud'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dynamic-crud.php' => config_path('dynamic-crud.php'),
        ], 'dynamic-crud-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/dynamic-crud'),
        ], 'dynamic-crud-views');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}