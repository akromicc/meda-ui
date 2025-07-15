<?php

namespace Meda\DynamicUI;

use Illuminate\Support\ServiceProvider;
use Meda\DynamicUI\Services\QuerySortingService;
use Meda\DynamicUI\Services\DynamicUIService;
use Meda\DynamicUI\Traits\HasMetadata;
use Meda\DynamicUI\Http\Controllers\DynamicController;

class DynamicUIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(QuerySortingService::class);
        $this->app->singleton(DynamicUIService::class);
        
        $this->mergeConfigFrom(
            __DIR__.'/../config/dynamic-ui.php', 'dynamic-ui'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dynamic-ui.php' => config_path('dynamic-ui.php'),
        ], 'dynamic-ui-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/dynamic-ui'),
        ], 'dynamic-ui-views');

        // Registrar rutas dinámicas
        $this->registerDynamicRoutes();
    }

    /**
     * Registrar rutas dinámicas basadas en configuración
     */
    protected function registerDynamicRoutes(): void
    {
        $config = config('dynamic-ui.routes', []);
        
        foreach ($config as $routeName => $routeConfig) {
            $this->registerRoute($routeName, $routeConfig);
        }
    }

    /**
     * Registrar una ruta dinámica
     */
    protected function registerRoute(string $name, array $config): void
    {
        $prefix = $config['prefix'] ?? 'api';
        $middleware = $config['middleware'] ?? ['api'];
        $model = $config['model'];
        $controller = $config['controller'] ?? DynamicController::class;

        $routeGroup = app('router')->group([
            'prefix' => $prefix,
            'middleware' => $middleware
        ], function () use ($name, $model, $controller) {
            // Rutas estándar
            app('router')->get("/{$name}", [$controller, 'index'])->name("{$name}.index");
            app('router')->post("/{$name}", [$controller, 'store'])->name("{$name}.store");
            app('router')->get("/{$name}/{id}", [$controller, 'show'])->name("{$name}.show");
            app('router')->put("/{$name}/{id}", [$controller, 'update'])->name("{$name}.update");
            app('router')->delete("/{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
            
            // Rutas adicionales si se especifican
            if (isset($config['additional_routes'])) {
                foreach ($config['additional_routes'] as $routeName => $routeConfig) {
                    $method = $routeConfig['method'] ?? 'get';
                    $action = $routeConfig['action'] ?? $routeName;
                    app('router')->$method("/{$name}/{id}/{$routeName}", [$controller, $action])->name("{$name}.{$routeName}");
                }
            }
        });
    }
}