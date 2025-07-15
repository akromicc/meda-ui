<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración del caché para metadatos de Dynamic UI
    |
    */
    'cache_timeout' => env('DYNAMIC_UI_CACHE_TIMEOUT', 3600), // 1 hora

    /*
    |--------------------------------------------------------------------------
    | Auto Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de rutas automáticas
    |
    */
    'auto_routes' => [
        // Habilitar sistema de rutas automáticas
        'enabled' => env('DYNAMIC_UI_AUTO_ROUTES', true),
        
        // Ruta base para endpoints dinámicos
        'base_path' => env('DYNAMIC_UI_BASE_PATH', '/dynamic'),
        
        // Middlewares aplicados a rutas automáticas
        'middleware' => ['api'], // agregar 'auth:api' si requieres autenticación
        
        // Modelos registrados automáticamente
        'models' => [
            // Alias => Configuración del modelo
            'users' => [
                'class' => 'App\\Models\\User',
                'config' => [
                    'relations' => [],
                    'searchColumns' => ['name', 'email'],
                    'perPage' => 15,
                    'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete']
                ]
            ],
            
            'products' => [
                'class' => 'App\\Models\\Product',
                'config' => [
                    'relations' => ['category'],
                    'searchColumns' => ['name', 'description', 'category.name'],
                    'perPage' => 20,
                    'permissions' => ['products.manage']
                ]
            ],
            
            // Agregar más modelos aquí...
        ],
        
        // Auto-descubrimiento de modelos
        'auto_discovery' => [
            'enabled' => true,
            'namespaces' => ['App\\Models\\'],
            'exclude' => ['User'] // Modelos a excluir del auto-descubrimiento
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de paginación por defecto
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'per_page_options' => [10, 15, 25, 50, 100],
        'max_per_page' => 100
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de validación automática
    |
    */
    'validation' => [
        'enabled' => true,
        'strict_mode' => false, // Si es true, requiere validación explícita
        'auto_generate_rules' => true // Generar reglas automáticamente de los metadatos
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad
    |
    */
    'security' => [
        // Verificar permisos automáticamente
        'check_permissions' => true,
        
        // Verificar ownership de registros
        'check_ownership' => false,
        
        // Campo de ownership (ej: 'user_id')
        'ownership_field' => 'user_id',
        
        // Modelos que requieren verificación de ownership
        'ownership_models' => [
            // 'App\\Models\\Post',
            // 'App\\Models\\Comment'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de la interfaz de usuario
    |
    */
    'ui' => [
        // Tema por defecto
        'theme' => 'light', // 'light', 'dark', 'auto'
        
        // Colores del tema
        'colors' => [
            'primary' => 'blue',
            'secondary' => 'gray',
            'success' => 'green',
            'warning' => 'yellow',
            'danger' => 'red',
            'info' => 'blue'
        ],
        
        // Configuración de iconos
        'icons' => [
            'provider' => 'fontawesome', // 'fontawesome', 'heroicons', 'tabler'
            'prefix' => 'fa'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Export/Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para exportación e importación
    |
    */
    'export' => [
        'enabled' => true,
        'formats' => ['csv', 'excel', 'json'],
        'max_records' => 10000,
        'chunk_size' => 1000
    ],

    'import' => [
        'enabled' => true,
        'max_file_size' => '10M',
        'allowed_types' => ['csv', 'xlsx'],
        'validate_headers' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de debug
    |
    */
    'debug' => [
        'enabled' => env('APP_DEBUG', false),
        'log_queries' => false,
        'log_metadata_generation' => false
    ]
];