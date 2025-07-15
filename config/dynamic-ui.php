<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de caché
    |--------------------------------------------------------------------------
    |
    | Tiempo de caché para metadatos en segundos
    |
    */
    'cache_timeout' => env('DYNAMIC_UI_CACHE_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Configuración de paginación
    |--------------------------------------------------------------------------
    |
    | Configuración por defecto para paginación
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'per_page_options' => [10, 15, 25, 50, 100],
        'max_per_page' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de filtros
    |--------------------------------------------------------------------------
    |
    | Configuración para filtros dinámicos
    |
    */
    'filters' => [
        'prefix' => 'f_',
        'enabled' => true,
        'date_format' => 'Y-m-d',
        'datetime_format' => 'Y-m-d H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de búsqueda
    |--------------------------------------------------------------------------
    |
    | Configuración para búsqueda dinámica
    |
    */
    'search' => [
        'enabled' => true,
        'min_length' => 2,
        'max_length' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de ordenamiento
    |--------------------------------------------------------------------------
    |
    | Configuración para ordenamiento dinámico
    |
    */
    'sorting' => [
        'default_sort_by' => 'created_at',
        'default_order' => 'desc',
        'allowed_orders' => ['asc', 'desc'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de relaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para cargar relaciones automáticamente
    |
    */
    'relations' => [
        'auto_load' => false,
        'max_depth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de validación
    |--------------------------------------------------------------------------
    |
    | Configuración para validación automática
    |
    */
    'validation' => [
        'auto_validate' => true,
        'strict_mode' => false,
        'custom_rules' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de permisos
    |--------------------------------------------------------------------------
    |
    | Configuración para verificación de permisos
    |
    */
    'permissions' => [
        'enabled' => true,
        'user_key' => 'user_id',
        'check_ownership' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de respuestas
    |--------------------------------------------------------------------------
    |
    | Configuración para respuestas JSON estandarizadas
    |
    */
    'responses' => [
        'include_stats' => true,
        'include_filters' => true,
        'include_metadata' => false,
        'success_key' => 'success',
        'data_key' => 'data',
        'message_key' => 'message',
        'error_key' => 'error',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de rutas dinámicas
    |--------------------------------------------------------------------------
    |
    | Configuración para registrar rutas automáticamente
    |
    */
    'routes' => [
        // Ejemplo de configuración de rutas
        // 'devices' => [
        //     'model' => \App\Models\Device::class,
        //     'controller' => \App\Http\Controllers\DeviceController::class,
        //     'prefix' => 'api',
        //     'middleware' => ['api', 'auth'],
        //     'additional_routes' => [
        //         'connect' => [
        //             'method' => 'post',
        //             'action' => 'connect'
        //         ],
        //         'disconnect' => [
        //             'method' => 'post',
        //             'action' => 'disconnect'
        //         ]
        //     ]
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de columnas por defecto
    |--------------------------------------------------------------------------
    |
    | Configuración para columnas automáticas
    |
    */
    'default_columns' => [
        'id' => [
            'type' => 'number',
            'width' => '80px',
            'sortable' => true,
            'filterable' => false,
        ],
        'created_at' => [
            'type' => 'date',
            'width' => '150px',
            'sortable' => true,
            'filterable' => true,
        ],
        'updated_at' => [
            'type' => 'date',
            'width' => '150px',
            'sortable' => true,
            'filterable' => true,
        ],
        'is_active' => [
            'type' => 'boolean',
            'width' => '100px',
            'sortable' => true,
            'filterable' => true,
        ],
        'status' => [
            'type' => 'select',
            'width' => '100px',
            'sortable' => true,
            'filterable' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de campos por defecto
    |--------------------------------------------------------------------------
    |
    | Configuración para campos automáticos
    |
    */
    'default_fields' => [
        'name' => [
            'type' => 'text',
            'required' => true,
            'validation' => 'required|string|max:255',
        ],
        'email' => [
            'type' => 'email',
            'required' => true,
            'validation' => 'required|email|unique:users,email',
        ],
        'phone' => [
            'type' => 'phone',
            'required' => false,
            'validation' => 'nullable|string',
        ],
        'description' => [
            'type' => 'textarea',
            'required' => false,
            'validation' => 'nullable|string',
        ],
        'is_active' => [
            'type' => 'boolean',
            'required' => false,
            'validation' => 'boolean',
            'default_value' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de opciones por defecto
    |--------------------------------------------------------------------------
    |
    | Configuración para opciones automáticas
    |
    */
    'default_options' => [
        'is_active' => [
            ['value' => 1, 'label' => 'Sí'],
            ['value' => 0, 'label' => 'No'],
        ],
        'status' => [
            ['value' => 'active', 'label' => 'Activo'],
            ['value' => 'inactive', 'label' => 'Inactivo'],
            ['value' => 'pending', 'label' => 'Pendiente'],
        ],
        'type' => [
            ['value' => 'user', 'label' => 'Usuario'],
            ['value' => 'admin', 'label' => 'Administrador'],
            ['value' => 'moderator', 'label' => 'Moderador'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de logging
    |--------------------------------------------------------------------------
    |
    | Configuración para logging de errores
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'dynamic-ui',
        'level' => 'error',
    ],
];