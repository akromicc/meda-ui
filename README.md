# Dynamic UI - Librería de Interfaces CRUD Dinámicas

Una librería completa para crear interfaces CRUD dinámicas con mínima configuración, usando metadatos del backend para generar automáticamente tablas, modales y acciones.

## ✨ Características Principales

- **🔄 Generación Automática de Acciones**: Las acciones se generan automáticamente de los modales definidos
- **📊 Tablas Dinámicas**: Configuración automática de columnas, filtros y ordenamiento
- **🎨 Modales Inteligentes**: Formularios, vistas y confirmaciones automáticas
- **⚡ Validación Automática**: Reglas de validación generadas del modelo
- **🎯 Cacheo Inteligente**: Metadatos cacheados para mejor rendimiento
- **📱 Diseño Responsive**: Componentes optimizados para móviles
- **🌙 Modo Oscuro**: Soporte completo para tema oscuro
- **🔧 Personalización Avanzada**: Override de cualquier configuración

## 🚀 Instalación

### Backend (Laravel)

```bash
composer require meda/dynamic-ui
```

### Frontend (Vue.js)

```bash
npm install @meda/dynamic-ui
```

## 📖 Uso Rápido

### 1. Configurar el Modelo

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Traits\HasMetadata;

class User extends Model
{
    use HasMetadata;

    protected $fillable = ['name', 'email', 'role', 'is_active'];

    /**
     * Definir modales (las acciones se generan automáticamente)
     */
    public function defineModals(): array
    {
        return [
            'create' => [
                'key' => 'create',
                'label' => 'Crear Usuario',
                'icon' => 'fa fa-plus',
                'color' => 'primary',
                'type' => 'form',
                'fields' => $this->getDefaultFields(),
                'method' => 'POST',
                'showAsButton' => true, // Botón principal
                'showInDropdown' => false
            ],
            'view' => [
                'key' => 'view',
                'label' => 'Ver Usuario',
                'icon' => 'fa fa-eye',
                'color' => 'info',
                'type' => 'view',
                'fields' => $this->getDefaultFields(),
                'showInDropdown' => true, // En menú tres puntos
                'showAsButton' => false
            ],
            'edit' => [
                'key' => 'edit',
                'label' => 'Editar Usuario',
                'icon' => 'fa fa-edit',
                'color' => 'warning',
                'type' => 'form',
                'fields' => $this->getDefaultFields(),
                'method' => 'PUT',
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'delete' => [
                'key' => 'delete',
                'label' => 'Eliminar Usuario',
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'type' => 'confirm',
                'confirmMessage' => '¿Estás seguro?',
                'method' => 'DELETE',
                'showInDropdown' => true,
                'showAsButton' => false
            ]
        ];
    }

    /**
     * Acciones personalizadas adicionales
     */
    public function defineCustomActions(): array
    {
        return [
            'toggle_status' => [
                'key' => 'toggle_status',
                'label' => 'Cambiar Estado',
                'icon' => 'fa fa-toggle-on',
                'color' => 'success',
                'type' => 'confirm',
                'method' => 'POST',
                'endpoint' => '/api/users/{id}/toggle-status',
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'duplicate' => [
                'key' => 'duplicate',
                'label' => 'Duplicar',
                'icon' => 'fa fa-copy',
                'color' => 'secondary',
                'type' => 'confirm',
                'method' => 'POST',
                'endpoint' => '/api/users/{id}/duplicate',
                'showInDropdown' => true,
                'showAsButton' => false
            ]
        ];
    }
}
```

### 2. Configurar el Controlador

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Meda\DynamicUI\Http\Controllers\DynamicController;
use Meda\DynamicUI\Services\DynamicUIService;

class UserController extends Controller
{
    protected $dynamicController;

    public function __construct(DynamicUIService $dynamicUIService)
    {
        $this->dynamicController = (new DynamicController($dynamicUIService))
            ->configure(User::class, [
                'relations' => [],
                'searchColumns' => ['name', 'email'],
                'perPage' => 15
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->dynamicController->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->dynamicController->store($request);
    }

    public function show(string $id): JsonResponse
    {
        return $this->dynamicController->show($id);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return $this->dynamicController->update($request, $id);
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->dynamicController->destroy($id);
    }

    public function metadata(): JsonResponse
    {
        return $this->dynamicController->metadata();
    }

    // Acciones personalizadas (se generan automáticamente de los modales)
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'data' => $user
        ]);
    }

    public function duplicate(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $newUser = $user->replicate();
        $newUser->name = $user->name . ' (Copia)';
        $newUser->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario duplicado',
            'data' => $newUser
        ]);
    }
}
```

### 3. Crear la Página Vue

```vue
<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Gestión de Usuarios</h1>
    
    <!-- Tabla con acciones automáticas -->
    <DataTable
      model-name="User"
      endpoint="/api/users"
      :auto-load="true"
      @action="handleAction"
    >
      <!-- Celdas personalizadas -->
      <template #cell-is_active="{ value }">
        <span :class="value ? 'text-green-600' : 'text-red-600'">
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>
    </DataTable>

    <!-- Modal para operaciones CRUD -->
    <DataModal
      v-model="showModal"
      :model-name="modalModelName"
      :modal-type="modalType"
      :modal-key="modalKey"
      :initial-data="modalData"
      @success="handleModalSuccess"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import { DataTable, DataModal } from '@meda/dynamic-ui'

export default {
  components: { DataTable, DataModal },
  
  setup() {
    const showModal = ref(false)
    const modalType = ref('')
    const modalKey = ref('')
    const modalData = ref({})
    const modalModelName = ref('User')

    const handleAction = ({ action, item }) => {
      // Las acciones se manejan automáticamente según el modal definido
      if (['view', 'edit', 'delete'].includes(action)) {
        modalType.value = action === 'delete' ? 'confirm' : action
        modalKey.value = action
        modalData.value = item
        showModal.value = true
      } else {
        // Acciones personalizadas (toggle_status, duplicate, etc.)
        console.log('Acción personalizada:', action, item)
      }
    }

    const handleModalSuccess = () => {
      showModal.value = false
      // Refrescar tabla
    }

    return {
      showModal,
      modalType,
      modalKey,
      modalData,
      modalModelName,
      handleAction,
      handleModalSuccess
    }
  }
}
</script>
```

## 🔧 Configuración Avanzada

### Personalizar Acciones

```php
// En el modelo
public function defineCustomActions(): array
{
    return [
        'export' => [
            'key' => 'export',
            'label' => 'Exportar',
            'icon' => 'fa fa-download',
            'color' => 'success',
            'type' => 'download',
            'method' => 'GET',
            'endpoint' => '/api/users/export',
            'showInDropdown' => true,
            'showAsButton' => false
        ],
        'bulk_delete' => [
            'key' => 'bulk_delete',
            'label' => 'Eliminar Seleccionados',
            'icon' => 'fa fa-trash-alt',
            'color' => 'danger',
            'type' => 'confirm',
            'method' => 'POST',
            'endpoint' => '/api/users/bulk-delete',
            'showInDropdown' => false,
            'showAsButton' => true
        ]
    ];
}
```

### Configurar Permisos

```php
// En el modal
'edit' => [
    'key' => 'edit',
    'label' => 'Editar',
    'permission' => 'users.edit', // Permiso requerido
    'showInDropdown' => true,
    'showAsButton' => false
]
```

### Validación Personalizada

```php
// En el modelo
protected function getDefaultFields(): array
{
    return [
        [
            'key' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'required' => true,
            'validation' => 'required|email|unique:users,email',
            'help' => 'El email debe ser único en el sistema'
        ]
    ];
}
```

## 🎨 Componentes Disponibles

### DataTable
Tabla dinámica con búsqueda, filtros, ordenamiento y paginación.

```vue
<DataTable
  model-name="User"
  endpoint="/api/users"
  :auto-load="true"
  :per-page="15"
  @action="handleAction"
/>
```

### DataModal
Modal universal para formularios, vistas y confirmaciones.

```vue
<DataModal
  v-model="showModal"
  :model-name="modelName"
  :modal-type="modalType"
  :modal-key="modalKey"
  :initial-data="data"
  @success="handleSuccess"
/>
```

### StatsGrid
Grid de tarjetas de estadísticas.

```vue
<StatsGrid :stats="stats" />
```

## 📚 API Reference

### Backend

#### DynamicUIService
- `getModelMetadata(string $modelClass, ?string $context = null): array`
- `processDynamicView(string $modelClass, Request $request, array $config = []): JsonResponse`

#### HasMetadata Trait
- `defineTable(): array`
- `defineModals(): array`
- `defineCustomActions(): array`
- `getAllModals(): array`

### Frontend

#### DataTable Props
- `model-name`: Nombre del modelo para cargar metadatos
- `endpoint`: URL del endpoint de datos
- `auto-load`: Cargar datos automáticamente
- `per-page`: Elementos por página
- `columns`: Columnas personalizadas
- `actions`: Acciones personalizadas

#### DataModal Props
- `model-name`: Nombre del modelo
- `modal-type`: Tipo de modal (form, view, confirm)
- `modal-key`: Clave del modal
- `initial-data`: Datos iniciales
- `fields`: Campos personalizados

## 🚀 Mejoras Implementadas

### ✅ Generación Automática de Acciones
- Las acciones se generan automáticamente de los modales definidos
- No es necesario definir acciones por separado
- Configuración más simple y mantenible

### ✅ Configuración Inteligente
- `showAsButton`: Controla si la acción aparece como botón principal
- `showInDropdown`: Controla si la acción aparece en el menú tres puntos
- Iconos y colores automáticos según el tipo de acción

### ✅ Acciones Personalizadas
- Método `defineCustomActions()` para acciones específicas
- Endpoints personalizados con parámetros dinámicos
- Tipos de acción: form, view, confirm, download

### ✅ Mejor UX
- Botón de crear siempre visible como acción principal
- Resto de acciones organizadas en menú tres puntos
- Confirmaciones automáticas para acciones destructivas

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🆘 Soporte

- 📧 Email: support@meda.com
- 💬 Discord: [Meda Community](https://discord.gg/meda)
- 📖 Documentación: [docs.meda.com](https://docs.meda.com)