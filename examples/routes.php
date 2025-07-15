<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/**
 * Ejemplo de rutas usando Dynamic UI
 * 
 * Este archivo muestra diferentes formas de configurar
 * rutas para usar con Dynamic UI.
 */

// ==========================================
// MÉTODO 1: Rutas completas con controlador personalizado
// ==========================================

Route::prefix('api/users')->middleware(['api', 'auth'])->group(function () {
    // CRUD básico
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    
    // Metadatos para Dynamic UI
    Route::get('/metadata', [UserController::class, 'metadata']);
    
    // Acciones personalizadas
    Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    
    // Búsqueda para campos tipo 'search'
    Route::get('/search', [UserController::class, 'search']);
});

// ==========================================
// MÉTODO 2: Usando resource route con acciones adicionales
// ==========================================

Route::apiResource('api/products', ProductController::class)->middleware(['api', 'auth']);
Route::prefix('api/products')->middleware(['api', 'auth'])->group(function () {
    Route::get('/metadata', [ProductController::class, 'metadata']);
    Route::post('/{id}/duplicate', [ProductController::class, 'duplicate']);
    Route::post('/{id}/toggle-featured', [ProductController::class, 'toggleFeatured']);
});

// ==========================================
// MÉTODO 3: Configuración simple con DynamicController directo
// ==========================================

Route::prefix('api/categories')->middleware(['api', 'auth'])->group(function () {
    $controller = app(\Meda\DynamicUI\Http\Controllers\DynamicController::class);
    $controller->configure(\App\Models\Category::class, [
        'relations' => ['parent'],
        'searchColumns' => ['name', 'description', 'parent.name'],
        'perPage' => 20
    ]);

    Route::get('/', [$controller, 'index']);
    Route::post('/', [$controller, 'store']);
    Route::get('/{id}', [$controller, 'show']);
    Route::put('/{id}', [$controller, 'update']);
    Route::delete('/{id}', [$controller, 'destroy']);
    Route::get('/metadata', [$controller, 'metadata']);
});

// ==========================================
// MÉTODO 4: Macro para simplificar registro de rutas
// ==========================================

if (!Route::hasMacro('dynamicResource')) {
    Route::macro('dynamicResource', function ($name, $modelClass, $config = []) {
        $controller = app(\Meda\DynamicUI\Http\Controllers\DynamicController::class);
        $controller->configure($modelClass, $config);

        Route::prefix("api/{$name}")->middleware(['api', 'auth'])->group(function () use ($controller) {
            Route::get('/', [$controller, 'index']);
            Route::post('/', [$controller, 'store']);
            Route::get('/{id}', [$controller, 'show']);
            Route::put('/{id}', [$controller, 'update']);
            Route::delete('/{id}', [$controller, 'destroy']);
            Route::get('/metadata', [$controller, 'metadata']);
        });
    });
}

// Usar el macro
Route::dynamicResource('orders', \App\Models\Order::class, [
    'relations' => ['user', 'items'],
    'searchColumns' => ['number', 'user.name', 'user.email'],
    'defaultSortBy' => 'created_at',
    'defaultOrder' => 'desc'
]);

// ==========================================
// RUTAS DE BÚSQUEDA GLOBAL
// ==========================================

Route::prefix('api/search')->middleware(['api', 'auth'])->group(function () {
    Route::get('/users', [UserController::class, 'search']);
    Route::get('/products', [ProductController::class, 'search']);
    Route::get('/categories', [CategoryController::class, 'search']);
    Route::get('/orders', [OrderController::class, 'search']);
});

// ==========================================
// RUTAS PARA METADATOS CENTRALIZADOS
// ==========================================

Route::prefix('api/metadata')->middleware(['api', 'auth'])->group(function () {
    Route::get('/users', [UserController::class, 'metadata']);
    Route::get('/products', [ProductController::class, 'metadata']);
    Route::get('/categories', [CategoryController::class, 'metadata']);
    Route::get('/orders', [OrderController::class, 'metadata']);
});

// ==========================================
// EJEMPLO DE CONFIGURACIÓN AVANZADA
// ==========================================

Route::prefix('api/advanced-users')->middleware(['api', 'auth'])->group(function () {
    $controller = app(\Meda\DynamicUI\Http\Controllers\DynamicController::class);
    $controller->configure(\App\Models\User::class, [
        'relations' => ['profile', 'roles'],
        'searchColumns' => ['name', 'email', 'profile.company', 'roles.name'],
        'perPage' => 25,
        'defaultSortBy' => 'last_login_at',
        'defaultOrder' => 'desc',
        'customQuery' => function ($query) {
            // Solo usuarios activos de los últimos 30 días
            return $query->where('is_active', true)
                        ->where('last_login_at', '>=', now()->subDays(30));
        },
        'additionalData' => [
            'stats' => [
                'total_active' => \App\Models\User::where('is_active', true)->count(),
                'total_recent' => \App\Models\User::where('last_login_at', '>=', now()->subDays(7))->count(),
                'total_premium' => \App\Models\User::where('is_premium', true)->count(),
            ]
        ]
    ]);

    Route::get('/', [$controller, 'index']);
    Route::post('/', [$controller, 'store']);
    Route::get('/{id}', [$controller, 'show']);
    Route::put('/{id}', [$controller, 'update']);
    Route::delete('/{id}', [$controller, 'destroy']);
    Route::get('/metadata', [$controller, 'metadata']);
});