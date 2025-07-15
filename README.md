# Dynamic UI - Librería de CRUD Dinámico

Una librería moderna para Laravel + Vue.js que permite crear interfaces CRUD completas usando solo metadatos del backend. Elimina la necesidad de crear formularios, tablas y modales repetitivos.

## 🎯 Objetivo Principal

**Resolver el problema de crear estructuras repetitivas** en aplicaciones web. Con Dynamic UI, defines los metadatos una vez en tu modelo de Laravel y obtienes automáticamente:

- ✅ Tablas con filtros, búsqueda y ordenamiento
- ✅ Modales para crear, editar y ver registros  
- ✅ Validación automática de formularios
- ✅ Estadísticas y métricas en cards
- ✅ Acciones personalizadas por registro
- ✅ Sistema de caché para metadatos
- ✅ Diseño responsive con Tailwind CSS

## 🚀 Instalación

### Backend (Laravel)

```bash
composer require meda/dynamic-ui
```

Publicar configuración:
```bash
php artisan vendor:publish --provider="Meda\DynamicUI\DynamicUIServiceProvider"
```

### Frontend (Vue.js)

```bash
npm install @meda/dynamic-ui
```

```javascript
// main.js
import { createApp } from 'vue'
import MedaUI from '@meda/dynamic-ui'

const app = createApp({})
app.use(MedaUI, {
  cacheTimeout: 3600000, // 1 hora
  metadataEndpoint: '/api/metadata'
})
```

## 📖 Uso Básico

### 1. Preparar tu Modelo (Laravel)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Traits\HasMetadata;

class Product extends Model
{
    use HasMetadata;

    protected $fillable = ['name', 'price', 'category_id', 'is_active'];
    
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // OPCIONAL: Personalizar metadatos (si no se define, se auto-genera)
    public function defineTable(): array
    {
        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'type' => 'number', 'width' => '80px'],
                ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'sortable' => true],
                ['key' => 'price', 'label' => 'Precio', 'type' => 'number', 'sortable' => true],
                ['key' => 'category.name', 'label' => 'Categoría', 'type' => 'text'],
                ['key' => 'is_active', 'label' => 'Activo', 'type' => 'boolean'],
            ],
            'relations' => ['category'],
            'searchColumns' => ['name', 'category.name']
        ];
    }

    // OPCIONAL: Personalizar modales (si no se define, se auto-genera todo)
    public function defineModals(): array
    {
        $fields = [
            [
                'key' => 'name',
                'label' => 'Nombre del Producto',
                'type' => 'text',
                'required' => true,
                'validation' => 'required|string|max:255'
            ],
            [
                'key' => 'price',
                'label' => 'Precio',
                'type' => 'number',
                'required' => true,
                'validation' => 'required|numeric|min:0'
            ],
            [
                'key' => 'category_id',
                'label' => 'Categoría',
                'type' => 'search',
                'searchEndpoint' => '/api/search/categories',
                'required' => true
            ]
        ];

        return [
            'create' => [
                'key' => 'create',
                'label' => 'Nuevo Producto',
                'icon' => 'fa fa-plus',
                'type' => 'form',
                'fields' => $fields,
                'method' => 'POST'
            ],
            'view' => [
                'key' => 'view',
                'label' => 'Ver',
                'icon' => 'fa fa-eye',
                'type' => 'view',
                'fields' => $fields
            ],
            'edit' => [
                'key' => 'edit',
                'label' => 'Editar',
                'icon' => 'fa fa-edit',
                'type' => 'form',
                'fields' => $fields,
                'method' => 'PUT'
            ],
            'delete' => [
                'key' => 'delete',
                'label' => 'Eliminar',
                'icon' => 'fa fa-trash',
                'type' => 'confirm',
                'confirmMessage' => '¿Eliminar producto?',
                'method' => 'DELETE'
            ],
            // Acción personalizada
            'duplicate' => [
                'key' => 'duplicate',
                'label' => 'Duplicar',
                'icon' => 'fa fa-copy',
                'type' => 'confirm',
                'confirmMessage' => '¿Duplicar este producto?',
                'endpoint' => '/duplicate',
                'method' => 'POST'
            ]
        ];
    }
}
```

### 2. Crear Rutas (Laravel)

```php
// routes/api.php
use App\Models\Product;
use Meda\DynamicUI\Http\Controllers\DynamicController;

Route::prefix('products')->group(function () {
    $controller = new DynamicController(app(\Meda\DynamicUI\Services\DynamicUIService::class));
    $controller->configure(Product::class);

    Route::get('/', [$controller, 'index']);
    Route::post('/', [$controller, 'store']);
    Route::get('/{id}', [$controller, 'show']);
    Route::put('/{id}', [$controller, 'update']);
    Route::delete('/{id}', [$controller, 'destroy']);
    Route::get('/metadata', [$controller, 'metadata']);
});
```

### 3. Usar en Vue.js

```vue
<template>
  <div class="space-y-6">
    <!-- Header con botón crear -->
    <div class="flex justify-between items-center">
      <h1 class="text-2xl font-bold">Productos</h1>
      <button @click="openCreateModal" class="btn-primary">
        Nuevo Producto
      </button>
    </div>

    <!-- Estadísticas (opcional) -->
    <MedaStatsGrid>
      <MedaStatCard 
        title="Total Productos" 
        :value="stats?.total || 0" 
        icon="fas fa-box"
      />
      <MedaStatCard 
        title="Activos" 
        :value="stats?.active || 0" 
        icon="fas fa-check-circle"
      />
    </MedaStatsGrid>

    <!-- Tabla con todas las funcionalidades -->
    <MedaDataTable
      ref="dataTable"
      endpoint="/api/products"
      model-name="products"
      @dataLoaded="handleDataLoaded"
      @action="handleAction"
    >
      <!-- Custom cells (opcional) -->
      <template #cell-price="{ value }">
        ${{ value.toFixed(2) }}
      </template>
    </MedaDataTable>

    <!-- Modal universal -->
    <MedaDataModal
      ref="dataModal"
      model-name="products"
      @success="handleSuccess"
    />
  </div>
</template>

<script>
import { ref } from 'vue'

export default {
  setup() {
    const dataTable = ref(null)
    const dataModal = ref(null)
    const stats = ref({})

    const handleDataLoaded = (data) => {
      stats.value = data.stats || {}
    }

    const openCreateModal = () => {
      dataModal.value.openModal({
        title: 'Crear Producto',
        endpoint: '/api/products',
        method: 'POST',
        actionType: 'form'
      })
    }

    const handleAction = ({ action, item }) => {
      switch (action) {
        case 'edit':
          dataModal.value.openModal({
            title: 'Editar Producto',
            endpoint: `/api/products/${item.id}`,
            method: 'PUT',
            actionType: 'form',
            item: item
          })
          break
        case 'delete':
          dataModal.value.openModal({
            title: 'Eliminar Producto',
            endpoint: `/api/products/${item.id}`,
            method: 'DELETE',
            actionType: 'confirm',
            item: item
          })
          break
      }
    }

    const handleSuccess = () => {
      dataTable.value.refresh()
    }

    return {
      dataTable,
      dataModal,
      stats,
      handleDataLoaded,
      openCreateModal,
      handleAction,
      handleSuccess
    }
  }
}
</script>
```

## 🧩 Componentes Principales

### MedaDataTable
Tabla completa con filtros, búsqueda, ordenamiento y paginación automática.

**Props principales:**
- `endpoint`: URL de la API
- `model-name`: Nombre del modelo para caché
- `per-page`: Elementos por página (default: 15)

**Eventos:**
- `@dataLoaded`: Cuando se cargan datos
- `@action`: Cuando se ejecuta una acción

### MedaDataModal
Modal universal para crear, editar, ver y confirmar acciones.

**Métodos:**
- `openModal(config)`: Abrir con configuración específica

### MedaStatsGrid & MedaStatCard
Sistema de tarjetas para mostrar estadísticas.

## 🎨 Personalización

### Custom Cells en Tablas

```vue
<MedaDataTable endpoint="/api/products">
  <!-- Personalizar celda de precio -->
  <template #cell-price="{ value, item }">
    <span class="font-bold text-green-600">
      ${{ value.toFixed(2) }}
    </span>
  </template>
  
  <!-- Personalizar celda de estado -->
  <template #cell-status="{ value }">
    <span :class="getStatusClass(value)">
      {{ getStatusLabel(value) }}
    </span>
  </template>
</MedaDataTable>
```

### Acciones Personalizadas

```php
// En defineModals() puedes agregar cualquier acción personalizada
public function defineModals(): array
{
    return [
        // ... otros modales (create, view, edit, delete)
        
        'duplicate' => [
            'key' => 'duplicate',
            'label' => 'Duplicar',
            'icon' => 'fa fa-copy',
            'color' => 'info',
            'type' => 'confirm',
            'confirmMessage' => '¿Duplicar este producto?',
            'endpoint' => '/duplicate', // Endpoint relativo
            'method' => 'POST'
        ],
        'export' => [
            'key' => 'export',
            'label' => 'Exportar',
            'icon' => 'fa fa-download',
            'color' => 'secondary',
            'type' => 'confirm',
            'confirmMessage' => '¿Exportar este producto?',
            'endpoint' => '/export',
            'method' => 'GET'
        ]
    ];
}
```

## ⚡ Features Avanzados

### Caché Automático
Los metadatos se cachean automáticamente para mejorar performance:

```php
// Limpiar caché cuando cambies metadatos
app(\Meda\DynamicUI\Services\DynamicUIService::class)
    ->clearMetadataCache(Product::class);
```

### Filtros Avanzados
```php
// En defineTable()
'filters' => [
    'price_range' => [
        'type' => 'number_range',
        'label' => 'Rango de Precio'
    ],
    'created_date' => [
        'type' => 'date_range', 
        'label' => 'Fecha de Creación'
    ]
]
```

### Búsqueda en Relaciones
```php
'searchColumns' => [
    'name',
    'description', 
    'r:category.name',     // Buscar en relación
    'r:supplier.company'   // Múltiples relaciones
]
```

## 🔧 Configuración

### Backend (config/dynamic-ui.php)
```php
return [
    'cache_timeout' => 3600,
    'pagination' => [
        'default_per_page' => 15,
        'per_page_options' => [10, 15, 25, 50]
    ],
    'permissions' => [
        'enabled' => true,
        'check_ownership' => true
    ]
];
```

### Frontend (Vue Plugin)
```javascript
app.use(MedaUI, {
  cacheTimeout: 3600000,
  metadataEndpoint: '/api/metadata',
  theme: {
    primary: 'blue',
    success: 'green'
  }
})
```

## 📦 Estructura del Proyecto

```
dynamic-ui/
├── src/                          # Backend Laravel
│   ├── Services/
│   │   ├── DynamicUIService.php  # Servicio principal  
│   │   └── QuerySortingService.php
│   ├── Http/Controllers/
│   │   └── DynamicController.php # Controlador universal
│   ├── Traits/
│   │   └── HasMetadata.php       # Trait para modelos
│   └── Contracts/
│       └── DynamicModel.php      # Interfaz opcional
├── npm/plugins/meda-ui/          # Frontend Vue.js
│   ├── components/
│   │   ├── DataTable/           # Tabla dinámica
│   │   ├── DataModal.vue        # Modal universal
│   │   └── Stats*.vue           # Componentes de stats
│   ├── composables/
│   │   ├── useMetadata.js       # Manejo de metadatos
│   │   └── useDataValidation.js # Validaciones
│   └── index.js                 # Plugin principal
└── config/
    └── dynamic-ui.php           # Configuración
```

## 🎯 Casos de Uso Ideales

- ✅ Paneles administrativos
- ✅ CRUDs de catálogos
- ✅ Gestión de usuarios
- ✅ Sistemas de inventario
- ✅ Cualquier interfaz con tablas + formularios repetitivos

## 🤝 Contribuir

1. Fork el proyecto
2. Crea tu feature branch (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push al branch (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

MIT License - ver archivo [LICENSE](LICENSE) para detalles.

---

**Dynamic UI** - Construye interfaces dinámicas con metadatos. Simple, potente, escalable.