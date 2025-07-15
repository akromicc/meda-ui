# DynamicUI - Librería para Vistas Dinámicas

Una librería completa para crear vistas dinámicas con tablas, paginación, búsqueda y componentes reutilizables en Laravel y Vue.js.

## Características

- ✅ **Tablas dinámicas** con paginación automática
- ✅ **Búsqueda avanzada** con filtros múltiples
- ✅ **Ordenamiento** por columnas
- ✅ **CRUD dinámico** con formularios automáticos
- ✅ **Rutas dinámicas** configurables
- ✅ **Componentes Vue.js** reutilizables
- ✅ **Metadatos automáticos** basados en modelos
- ✅ **Caché inteligente** para mejor rendimiento
- ✅ **Validación automática** de formularios
- ✅ **Permisos integrados** para seguridad

## Instalación

### Composer (PHP)

```bash
composer require meda/dynamic-ui
```

### NPM (Vue.js)

```bash
npm install @meda/dynamic-ui
```

## Configuración

### Laravel

1. **Publicar configuración:**

```bash
php artisan vendor:publish --tag=dynamic-ui-config
```

2. **Registrar ServiceProvider** (automático con composer):

```php
// config/app.php
'providers' => [
    // ...
    Meda\DynamicUI\DynamicUIServiceProvider::class,
];
```

3. **Configurar rutas dinámicas:**

```php
// config/dynamic-ui.php
'routes' => [
    'devices' => [
        'model' => \App\Models\Device::class,
        'controller' => \App\Http\Controllers\DeviceController::class,
        'prefix' => 'api',
        'middleware' => ['api', 'auth'],
        'additional_routes' => [
            'connect' => [
                'method' => 'post',
                'action' => 'connect'
            ]
        ]
    ],
],
```

### Vue.js

```javascript
// main.js
import { createApp } from 'vue'
import DynamicUI from '@meda/dynamic-ui'
import '@meda/dynamic-ui/dist/style.css'

const app = createApp(App)

app.use(DynamicUI, {
  metadataEndpoint: '/api/metadata',
  dataEndpoint: '/api',
  cacheTimeout: 60 * 60 * 1000,
  pagination: {
    perPage: 15,
    perPageOptions: [10, 15, 25, 50, 100]
  }
})

app.mount('#app')
```

## Uso Básico

### 1. Configurar Modelo

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Traits\HasMetadata;

class Device extends Model
{
    use HasMetadata;

    protected $fillable = [
        'name',
        'status',
        'is_active',
        'user_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Definir configuración de tabla
    public function defineTable(): array
    {
        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'type' => 'number', 'sortable' => true],
                ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'sortable' => true, 'filterable' => true],
                ['key' => 'status', 'label' => 'Estado', 'type' => 'select', 'sortable' => true, 'filterable' => true],
                ['key' => 'is_active', 'label' => 'Activo', 'type' => 'boolean', 'sortable' => true],
                ['key' => 'user.name', 'label' => 'Usuario', 'type' => 'search', 'filterable' => true],
                ['key' => 'created_at', 'label' => 'Fecha', 'type' => 'date', 'sortable' => true]
            ],
            'options' => [
                'status' => [
                    ['value' => 'connected', 'label' => 'Conectado'],
                    ['value' => 'disconnected', 'label' => 'Desconectado']
                ],
                'is_active' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No']
                ]
            ],
            'searchColumns' => ['name', 'user.name', 'user.email'],
            'relations' => ['user']
        ];
    }

    // Definir configuración de modal
    public function defineModal(): array
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Nombre del Dispositivo',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ej: Mi WhatsApp'
                ],
                [
                    'key' => 'user_id',
                    'label' => 'Usuario',
                    'type' => 'search',
                    'required' => true,
                    'searchEndpoint' => '/api/search/users'
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'defaultValue' => true
                ]
            ],
            'title' => 'Crear Dispositivo',
            'submitText' => 'Guardar'
        ];
    }
}
```

### 2. Crear Vista Vue

```vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold">Dispositivos</h2>
        <p class="mt-1 text-sm text-gray-500">
          Gestiona tus dispositivos conectados
        </p>
      </div>
      <div class="mt-4 md:mt-0">
        <button
          @click="openCreateModal"
          class="btn btn-primary"
        >
          <i class="fas fa-plus mr-2"></i>
          Nuevo Dispositivo
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <DynamicStatsGrid>
      <DynamicStatCard
        icon="fas fa-mobile-alt"
        title="Total Dispositivos"
        :value="stats?.total_devices || 0"
        :loading="!stats"
        color="primary"
      />
      
      <DynamicStatCard
        icon="fas fa-check-circle"
        title="Conectados"
        :value="stats?.connected_devices || 0"
        :loading="!stats"
        color="success"
      />
    </DynamicStatsGrid>

    <!-- Data Table -->
    <DynamicDataTable
      ref="dataTable"
      endpoint="/api/devices"
      model-name="devices"
      @data-loaded="handleDataLoaded"
      @action="handleAction"
    >
      <!-- Custom Status Cell -->
      <template #cell-status="{ value }">
        <span
          :class="['badge', getStatusColor(value)]"
        >
          {{ getStatusLabel(value) }}
        </span>
      </template>

      <!-- Custom User Cell -->
      <template #cell-user="{ item }">
        <div class="flex items-center">
          <div class="avatar">
            <span>{{ item.user?.name?.charAt(0) }}</span>
          </div>
          <div class="ml-3">
            <div class="font-medium">{{ item.user?.name }}</div>
            <div class="text-sm text-gray-500">{{ item.user?.email }}</div>
          </div>
        </div>
      </template>
    </DynamicDataTable>

    <!-- Data Modal -->
    <DynamicDataModal
      ref="dataModal"
      model-name="devices"
      @close="closeModal"
      @success="handleModalSuccess"
      @error="handleModalError"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import { useDynamicData } from '@meda/dynamic-ui'

export default {
  name: 'DevicesPage',
  setup() {
    const dataTable = ref(null)
    const dataModal = ref(null)
    const stats = ref({})

    const { loadData } = useDynamicData('/api/devices')

    const handleDataLoaded = (data) => {
      stats.value = data.stats || {}
    }

    const handleAction = ({ action, item }) => {
      switch (action) {
        case 'view':
          openViewModal(item)
          break
        case 'edit':
          openEditModal(item)
          break
        case 'delete':
          openDeleteModal(item)
          break
      }
    }

    const openCreateModal = () => {
      dataModal.value?.open('create')
    }

    const openViewModal = (item) => {
      dataModal.value?.open('view', item)
    }

    const openEditModal = (item) => {
      dataModal.value?.open('edit', item)
    }

    const openDeleteModal = (item) => {
      if (confirm(`¿Eliminar dispositivo "${item.name}"?`)) {
        // Lógica de eliminación
      }
    }

    const closeModal = () => {
      dataModal.value?.close()
    }

    const handleModalSuccess = (data) => {
      dataTable.value?.reload()
      closeModal()
    }

    const handleModalError = (error) => {
      console.error('Error en modal:', error)
    }

    const getStatusLabel = (status) => {
      const labels = {
        connected: 'Conectado',
        disconnected: 'Desconectado'
      }
      return labels[status] || status
    }

    const getStatusColor = (status) => {
      const colors = {
        connected: 'badge-success',
        disconnected: 'badge-danger'
      }
      return colors[status] || 'badge-secondary'
    }

    return {
      dataTable,
      dataModal,
      stats,
      handleDataLoaded,
      handleAction,
      openCreateModal,
      openViewModal,
      openEditModal,
      openDeleteModal,
      closeModal,
      handleModalSuccess,
      handleModalError,
      getStatusLabel,
      getStatusColor
    }
  }
}
</script>
```

### 3. Usar Composable

```vue
<script>
import { useDynamicData } from '@meda/dynamic-ui'

export default {
  setup() {
    const {
      metadata,
      state,
      loadData,
      createData,
      updateData,
      deleteData,
      searchData
    } = useDynamicData('/api/devices', {
      relations: ['user'],
      searchColumns: ['name', 'user.name'],
      perPage: 20
    })

    // Cargar datos
    const loadDevices = async () => {
      await loadData()
    }

    // Crear dispositivo
    const createDevice = async (data) => {
      await createData(data)
    }

    // Buscar dispositivos
    const searchDevices = async (query) => {
      await searchData(query)
    }

    return {
      metadata,
      state,
      loadDevices,
      createDevice,
      searchDevices
    }
  }
}
</script>
```

## API Reference

### PHP

#### QuerySortingService

```php
use Meda\DynamicUI\Services\QuerySortingService;

$service = new QuerySortingService();

$response = $service->getJsonResponse(
    $model,
    $request,
    $relations,
    $searchColumns,
    $customQuery,
    $perPage,
    $defaultSortBy,
    $defaultOrder,
    $additionalData
);
```

#### DynamicUIService

```php
use Meda\DynamicUI\Services\DynamicUIService;

$service = new DynamicUIService();

$metadata = $service->getModelMetadata(Device::class);
$response = $service->processDynamicView(Device::class, $request, $config);
```

#### HasMetadata Trait

```php
class Device extends Model
{
    use HasMetadata;

    public function defineTable(): array
    {
        // Configuración de tabla
    }

    public function defineModal(): array
    {
        // Configuración de modal
    }
}
```

### Vue.js

#### useDynamicUI

```javascript
import { useDynamicUI } from '@meda/dynamic-ui'

const {
  config,
  state,
  setLoading,
  setData,
  setFilters,
  setSearch,
  buildUrl,
  addFilter,
  removeFilter
} = useDynamicUI()
```

#### useDynamicData

```javascript
import { useDynamicData } from '@meda/dynamic-ui'

const {
  metadata,
  state,
  loadData,
  createData,
  updateData,
  deleteData,
  searchData
} = useDynamicData('/api/devices', options)
```

## Configuración Avanzada

### Rutas Personalizadas

```php
// config/dynamic-ui.php
'routes' => [
    'devices' => [
        'model' => \App\Models\Device::class,
        'controller' => \App\Http\Controllers\CustomDeviceController::class,
        'prefix' => 'api',
        'middleware' => ['api', 'auth'],
        'additional_routes' => [
            'connect' => [
                'method' => 'post',
                'action' => 'connect'
            ],
            'disconnect' => [
                'method' => 'post',
                'action' => 'disconnect'
            ],
            'qr-code' => [
                'method' => 'get',
                'action' => 'getQrCode'
            ]
        ]
    ],
],
```

### Componentes Personalizados

```vue
<template>
  <DynamicDataTable
    endpoint="/api/devices"
    :custom-columns="customColumns"
    :custom-actions="customActions"
  >
    <!-- Slots personalizados -->
    <template #header-actions>
      <button @click="exportData">Exportar</button>
    </template>

    <template #cell-custom="{ item }">
      <CustomCell :item="item" />
    </template>
  </DynamicDataTable>
</template>

<script>
export default {
  data() {
    return {
      customColumns: [
        {
          key: 'custom',
          label: 'Personalizado',
          type: 'custom',
          width: '200px'
        }
      ],
      customActions: [
        {
          key: 'custom_action',
          label: 'Acción Personalizada',
          icon: 'fas fa-star',
          handler: this.handleCustomAction
        }
      ]
    }
  },
  methods: {
    handleCustomAction(item) {
      console.log('Acción personalizada:', item)
    },
    exportData() {
      // Lógica de exportación
    }
  }
}
</script>
```

## Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## Soporte

- 📧 Email: team@meda.com
- 📖 Documentación: [docs.meda.com](https://docs.meda.com)
- 🐛 Issues: [GitHub Issues](https://github.com/meda/dynamic-ui/issues)