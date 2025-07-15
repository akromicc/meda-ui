# MedaUI - Sistema Súper Simple para Tablas y Modales Dinámicos

## 🚀 Descripción

MedaUI es un plugin de Vue 3 que proporciona un sistema **súper simple** para crear tablas y modales dinámicos conectados automáticamente a tu backend Laravel. Con solo definir 2 arrays simples en tus modelos, obtienes una interfaz completa y funcional.

## ✨ Características

- **Súper Simple**: Solo 2 métodos por modelo (`defineTable()` y `defineModal()`)
- **Cache Automático**: 1 hora de cache para todos los metadatos
- **Valores Automáticos**: El sistema agrega automáticamente endpoints, acciones, títulos, mensajes
- **Sin Callbacks Complejos**: Eliminados todos los métodos callback complicados
- **Estructura Simple**: Los modelos solo definen arrays directos
- **Integración Fácil**: Plugin que se instala globalmente

## 📦 Instalación

### 1. Backend (Laravel)

#### Instalar el Trait en tu Modelo

```php
<?php

namespace App\Models;

use App\Models\Traits\HasMetadata;

class Device extends Model
{
    use HasMetadata;
    
    // Solo necesitas definir estos 2 métodos:
    
    public function defineTable(): array
    {
        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'type' => 'number'],
                ['key' => 'name', 'label' => 'Nombre', 'sortable' => true, 'filterable' => true, 'type' => 'text'],
                ['key' => 'status', 'label' => 'Estado', 'sortable' => true, 'filterable' => true, 'type' => 'select'],
                ['key' => 'created_at', 'label' => 'Fecha', 'sortable' => true, 'type' => 'date']
            ],
            'searchColumns' => ['name', 'description'],
            'relations' => ['user'],
            'filterOptions' => [
                'status' => [
                    ['value' => 'active', 'label' => 'Activo'],
                    ['value' => 'inactive', 'label' => 'Inactivo']
                ]
            ]
        ];
    }

    public function defineModal(): array
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ingresa el nombre'
                ],
                [
                    'key' => 'status',
                    'label' => 'Estado',
                    'type' => 'select',
                    'required' => true,
                    'optionsKey' => 'status_options'
                ]
            ],
            'fieldOptions' => [
                'status_options' => [
                    ['value' => 'active', 'label' => 'Activo'],
                    ['value' => 'inactive', 'label' => 'Inactivo']
                ]
            ]
        ];
    }
}
```

#### Crear el Trait HasMetadata

```php
<?php

namespace App\Models\Traits;

trait HasMetadata
{
    // Este trait solo marca que el modelo tiene metadatos
    // Los métodos defineTable() y defineModal() se definen en cada modelo
}
```

### 2. Frontend (Vue 3)

#### Instalar el Plugin

```javascript
// main.js
import { createApp } from 'vue'
import MedaUI from './plugins/meda-ui/index.js'

const app = createApp(App)

app.use(MedaUI, {
  cacheTimeout: 60 * 60 * 1000, // 1 hora
  metadataEndpoint: '/api/metadata'
})

app.mount('#app')
```

#### Usar en tus Componentes

```vue
<template>
  <div>
    <!-- Tabla Dinámica -->
    <MedaDataTable
      ref="dataTable"
      :endpoint="endpoint"
      :columns="columns"
      :actions="actions"
      @action="handleAction"
    />

    <!-- Modal Dinámico -->
    <MedaDataModal
      ref="dataModal"
      :fields="modalFields"
      @success="handleSuccess"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useMetadata } from '../plugins/meda-ui/composables/useMetadata.js'

export default {
  setup() {
    const { getTableMetadata, getModalMetadata } = useMetadata()
    
    const dataTable = ref(null)
    const dataModal = ref(null)
    const endpoint = '/api/devices'
    
    // Metadatos dinámicos
    const tableMetadata = ref(null)
    const modalMetadata = ref(null)
    
    // Computed basados en metadatos
    const columns = computed(() => tableMetadata.value?.columns || [])
    const actions = computed(() => tableMetadata.value?.actions || [])
    const modalFields = computed(() => modalMetadata.value?.fields || [])

    // Cargar metadatos
    const loadMetadata = async () => {
      tableMetadata.value = await getTableMetadata('devices')
      modalMetadata.value = await getModalMetadata('devices')
    }

    const handleAction = ({ action, item }) => {
      if (action === 'edit') {
        dataModal.value.openModal({
          title: 'Editar',
          endpoint: `${endpoint}/${item.id}`,
          method: 'PUT',
          actionType: 'form',
          item: item
        })
      }
    }

    const handleSuccess = () => {
      dataTable.value.refresh()
    }

    onMounted(loadMetadata)

    return {
      dataTable,
      dataModal,
      endpoint,
      columns,
      actions,
      modalFields,
      handleAction,
      handleSuccess
    }
  }
}
</script>
```

## 🔧 API Reference

### Backend

#### MetadataController

- `GET /api/metadata/table/{model}` - Obtener metadatos de tabla
- `GET /api/metadata/modal/{model}` - Obtener metadatos de modal  
- `GET /api/metadata` - Obtener todos los metadatos
- `DELETE /api/metadata/cache` - Limpiar cache

#### Estructura defineTable()

```php
[
    'columns' => [
        [
            'key' => 'campo_db',           // Campo de la base de datos
            'label' => 'Etiqueta',        // Texto que se muestra
            'sortable' => true,           // Si se puede ordenar
            'filterable' => true,         // Si se puede filtrar
            'type' => 'text',            // text, number, date, boolean, select
            'width' => '100px'           // Ancho opcional
        ]
    ],
    'searchColumns' => ['campo1', 'campo2'],  // Campos para búsqueda global
    'relations' => ['user', 'category'],      // Relaciones a cargar
    'filterOptions' => [                      // Opciones para filtros select
        'status' => [
            ['value' => 'active', 'label' => 'Activo']
        ]
    ]
]
```

#### Estructura defineModal()

```php
[
    'fields' => [
        [
            'key' => 'campo_db',          // Campo de la base de datos
            'label' => 'Etiqueta',       // Texto que se muestra
            'type' => 'text',           // text, number, date, boolean, select
            'required' => true,         // Si es obligatorio
            'placeholder' => 'Texto',   // Placeholder
            'validation' => 'required', // Reglas de validación Laravel
            'optionsKey' => 'users'     // Para selects, key en fieldOptions
        ]
    ],
    'fieldOptions' => [                 // Opciones para campos select
        'users' => [
            ['value' => 1, 'label' => 'Usuario 1', 'description' => 'user@email.com']
        ]
    ]
]
```

### Frontend

#### useMetadata()

```javascript
const {
  getTableMetadata,    // (model) => Promise<metadata>
  getModalMetadata,    // (model) => Promise<metadata>
  getAllMetadata,      // () => Promise<{tables, modals}>
  clearCache,          // () => void
  clearServerCache     // () => Promise<boolean>
} = useMetadata()
```

#### MedaDataTable Props

```javascript
{
  endpoint: String,        // URL del API
  columns: Array,          // Columnas de la tabla
  actions: Array,          // Acciones por fila
  searchPlaceholder: String, // Placeholder del buscador
  autoLoad: Boolean        // Auto-cargar datos (default: true)
}
```

#### MedaDataModal Props

```javascript
{
  fields: Array           // Campos del formulario
}
```

## 🎯 Casos de Uso

### Tabla Simple

```php
// Modelo
public function defineTable(): array
{
    return [
        'columns' => [
            ['key' => 'name', 'label' => 'Nombre', 'sortable' => true, 'type' => 'text'],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true, 'type' => 'text']
        ]
    ];
}
```

### Modal con Select Dinámico

```php
// Modelo
public function defineModal(): array
{
    return [
        'fields' => [
            [
                'key' => 'user_id',
                'label' => 'Usuario',
                'type' => 'select',
                'required' => true,
                'optionsKey' => 'users'
            ]
        ],
        'fieldOptions' => [
            'users' => $this->getUserOptions() // Método que devuelve array de opciones
        ]
    ];
}

private function getUserOptions(): array
{
    return User::select('id', 'name', 'email')
        ->get()
        ->map(fn($user) => [
            'value' => $user->id,
            'label' => $user->name,
            'description' => $user->email
        ])
        ->toArray();
}
```

### Tabla con Filtros

```php
public function defineTable(): array
{
    return [
        'columns' => [
            ['key' => 'status', 'label' => 'Estado', 'filterable' => true, 'type' => 'select']
        ],
        'filterOptions' => [
            'status' => [
                ['value' => 'active', 'label' => 'Activo'],
                ['value' => 'inactive', 'label' => 'Inactivo']
            ]
        ]
    ];
}
```

## 🚀 Ventajas

1. **Súper Simple**: Solo 2 arrays por modelo
2. **Cache Automático**: Sin preocuparte por performance
3. **Valores Automáticos**: Endpoints, títulos, mensajes se generan solos
4. **Sin Complejidad**: No más callbacks complicados
5. **Fácil Mantenimiento**: Cambias el array y todo se actualiza
6. **Escalable**: Funciona para cualquier modelo

## 🔄 Migración desde Sistema Anterior

Si tenías el sistema anterior con callbacks, simplemente:

1. Elimina todos los métodos callback complejos
2. Crea `defineTable()` con un array simple
3. Crea `defineModal()` con un array simple
4. El plugin maneja todo automáticamente

## 📝 Notas

- Cache por defecto: 1 hora
- Todos los valores son opcionales excepto `columns` y `fields`
- El sistema agrega automáticamente valores por defecto sensatos
- Compatible con cualquier modelo que use el trait `HasMetadata`