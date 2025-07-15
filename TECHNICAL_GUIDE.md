# Dynamic UI - Guía Técnica Completa

## 🏗️ Arquitectura de la Librería

Dynamic UI está construida con una arquitectura modular que separa claramente las responsabilidades entre backend (Laravel) y frontend (Vue.js).

### Backend (Laravel)
```
src/
├── Services/
│   ├── DynamicUIService.php      # Servicio principal de metadatos
│   └── QuerySortingService.php   # Servicio de consultas avanzadas
├── Http/Controllers/
│   └── DynamicController.php     # Controlador universal CRUD
├── Traits/
│   └── HasMetadata.php           # Trait para modelos
└── Contracts/
    └── DynamicModel.php          # Interfaz (opcional)
```

### Frontend (Vue.js)
```
npm/plugins/meda-ui/
├── components/
│   ├── DataTable/               # Componente de tabla
│   ├── DataModal.vue           # Modal universal
│   └── Stat*.vue               # Componentes de estadísticas
├── composables/
│   ├── useMetadata.js          # Manejo de metadatos
│   └── useDataValidation.js    # Validaciones
└── index.js                    # Plugin principal
```

## 📋 Especificación de Metadatos

### Estructura de Tabla (defineTable)
```php
public function defineTable(): array
{
    return [
        'columns' => [
            [
                'key' => 'field_name',           // Campo de la base de datos
                'label' => 'Etiqueta Visible',   // Texto mostrado en header
                'type' => 'text',                // Tipo de datos (ver tipos)
                'sortable' => true,              // Permite ordenamiento
                'filterable' => true,            // Permite filtrado
                'width' => 'auto',               // Ancho de columna
                'hidden' => false,               // Ocultar columna
                'searchable' => true             // Incluir en búsqueda
            ]
        ],
        'options' => [
            'field_name' => [                   // Opciones para campos select
                ['value' => 'val1', 'label' => 'Opción 1'],
                ['value' => 'val2', 'label' => 'Opción 2']
            ]
        ],
        'searchColumns' => ['field1', 'field2'], // Campos para búsqueda
        'relations' => ['relation1', 'relation2'] // Relaciones a cargar
    ];
}
```

### Tipos de Columna Soportados
| Tipo | Descripción | Uso |
|------|-------------|-----|
| `text` | Texto simple | Nombres, descripciones |
| `email` | Email con enlace | Direcciones de correo |
| `phone` | Teléfono | Números telefónicos |
| `number` | Número | IDs, cantidades |
| `date` | Fecha formateada | Fechas y timestamps |
| `boolean` | Verdadero/Falso | Estados activo/inactivo |
| `select` | Lista desplegable | Estados, categorías |
| `image` | Imagen | URLs de imágenes |
| `url` | Enlace | URLs externas |
| `custom` | Personalizado | Para custom cells |

### Estructura de Modal (defineModal)
```php
public function defineModal(): array
{
    return [
        'fields' => [
            [
                'key' => 'field_name',
                'label' => 'Etiqueta del Campo',
                'type' => 'text',              // Tipo de input
                'required' => true,            // Campo obligatorio
                'placeholder' => 'Texto...',   // Placeholder
                'validation' => 'required|string|max:255', // Reglas Laravel
                'defaultValue' => 'valor',     // Valor por defecto
                'options' => [],               // Para select/radio
                'multiple' => false,           // Para select múltiple
                'searchEndpoint' => '/api/search', // Para campos de búsqueda
                'hideInForm' => false,         // Ocultar en formulario
                'hideInView' => false,         // Ocultar en vista
                'readonly' => false,           // Solo lectura
                'help' => 'Texto de ayuda'     // Texto de ayuda
            ]
        ]
    ];
}
```

### Tipos de Campo Soportados
| Tipo | HTML Input | Descripción |
|------|------------|-------------|
| `text` | `<input type="text">` | Texto simple |
| `email` | `<input type="email">` | Email con validación |
| `password` | `<input type="password">` | Contraseña |
| `number` | `<input type="number">` | Números |
| `tel` | `<input type="tel">` | Teléfono |
| `url` | `<input type="url">` | URL |
| `date` | `<input type="date">` | Fecha |
| `datetime` | `<input type="datetime-local">` | Fecha y hora |
| `time` | `<input type="time">` | Hora |
| `textarea` | `<textarea>` | Texto largo |
| `select` | `<select>` | Lista desplegable |
| `radio` | `<input type="radio">` | Opciones mutuamente excluyentes |
| `checkbox` | `<input type="checkbox">` | Casillas de verificación |
| `boolean` | `<input type="checkbox">` | Verdadero/Falso |
| `file` | `<input type="file">` | Subida de archivos |
| `image` | `<input type="file" accept="image/*">` | Imágenes |
| `search` | Componente personalizado | Búsqueda en otra tabla |
| `hidden` | `<input type="hidden">` | Campo oculto |

### Estructura de Acciones (defineActionModals)
```php
public function defineActionModals(): array
{
    return [
        'action_key' => [
            'key' => 'action_key',
            'name' => 'action_key',
            'label' => 'Texto del Botón',
            'icon' => 'fa fa-icon',        // Clase de icono
            'color' => 'primary',          // Color del botón
            'type' => 'confirm',           // Tipo de acción
            'condition' => 'field === "value"', // Condición JS
            'confirmMessage' => '¿Confirmar?',   // Mensaje de confirmación
            'endpoint' => '/api/custom',   // Endpoint personalizado
            'method' => 'POST'             // Método HTTP
        ]
    ];
}
```

### Tipos de Acción
| Tipo | Descripción | Comportamiento |
|------|-------------|----------------|
| `view` | Ver registro | Abre modal en modo solo lectura |
| `form` | Editar registro | Abre modal con formulario |
| `confirm` | Confirmar acción | Muestra confirmación antes de ejecutar |
| `custom` | Acción personalizada | Ejecuta lógica personalizada |

## 🔧 Configuración Avanzada

### Cache de Metadatos
```php
// config/dynamic-ui.php
'cache_timeout' => 3600, // 1 hora en segundos

// Limpiar cache manualmente
app(\Meda\DynamicUI\Services\DynamicUIService::class)
    ->clearMetadataCache(User::class);
```

### Búsqueda Avanzada
```php
'searchColumns' => [
    'name',                    // Campo directo
    'description',            // Otro campo directo
    'r:user.name',           // Búsqueda en relación
    'r:user.email',          // Múltiples campos de relación
    'r:category.name'        // Relaciones anidadas
]
```

### Filtros Dinámicos
```php
// En la URL: /api/users?f_status=active&f_is_active=1&f_created_at=2024-01-01_2024-01-31
'filters' => [
    'f_status' => 'active',           // Filtro por campo
    'f_is_active' => '1',             // Filtro booleano
    'f_created_at' => '2024-01-01_2024-01-31', // Rango de fechas
    'f_user_id:user.name' => 'Juan'   // Filtro en relación
]
```

### Ordenamiento Personalizado
```php
// En la URL: /api/users?sortBy=created_at&order=desc
'defaultSortBy' => 'created_at',
'defaultOrder' => 'desc'
```

### Paginación
```php
// En la URL: /api/users?per_page=25&page=2
'pagination' => [
    'default_per_page' => 15,
    'per_page_options' => [10, 15, 25, 50, 100],
    'max_per_page' => 1000
]
```

## 🎨 Personalización de Frontend

### Custom Cells en Tablas
```vue
<MedaDataTable endpoint="/api/users">
  <!-- Cell personalizada para estado -->
  <template #cell-status="{ value, item, column }">
    <span :class="getStatusClass(value)">
      {{ getStatusLabel(value) }}
    </span>
  </template>
  
  <!-- Cell personalizada para acciones -->
  <template #cell-actions="{ item }">
    <button @click="customAction(item)">
      Acción Custom
    </button>
  </template>
</MedaDataTable>
```

### Slots Disponibles en DataTable
| Slot | Parámetros | Descripción |
|------|------------|-------------|
| `cell-{field}` | `{ value, item, column }` | Personalizar celda específica |
| `header-{field}` | `{ column }` | Personalizar header de columna |
| `actions` | `{ item }` | Personalizar columna de acciones |
| `empty` | `{}` | Estado cuando no hay datos |
| `loading` | `{}` | Estado de carga |

### Eventos de DataTable
```vue
<MedaDataTable
  @dataLoaded="handleDataLoaded"     // Cuando se cargan datos
  @action="handleAction"             // Cuando se ejecuta acción
  @filterChanged="handleFilter"      // Cuando cambia filtro
  @sortChanged="handleSort"          // Cuando cambia orden
  @pageChanged="handlePage"          // Cuando cambia página
/>
```

### Configuración de Modal
```javascript
dataModal.value.openModal({
  title: 'Título del Modal',
  endpoint: '/api/endpoint',
  method: 'POST',              // GET, POST, PUT, DELETE
  actionType: 'form',          // form, view, confirm
  item: dataItem,              // Item para editar/ver
  submitText: 'Guardar',       // Texto del botón
  loadingText: 'Guardando...',  // Texto durante carga
  confirmMessage: '¿Confirmar?', // Para type="confirm"
  size: 'lg',                  // sm, md, lg, xl
  persistent: false,           // No cerrar al hacer click fuera
  columns: 2                   // Columnas del formulario
})
```

## 📡 API Response Format

### Respuesta de Listado
```json
{
  "success": true,
  "data": {
    "data": [...],           // Array de registros
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7,
    "from": 1,
    "to": 15
  },
  "stats": {                 // Estadísticas adicionales
    "total_users": 100,
    "active_users": 85
  },
  "filters": {               // Filtros aplicados
    "search": "texto",
    "status": "active"
  }
}
```

### Respuesta de Metadatos
```json
{
  "success": true,
  "data": {
    "table": {
      "columns": [...],
      "options": {...},
      "searchColumns": [...],
      "relations": [...]
    },
    "modal": {
      "fields": [...]
    },
    "actions": {
      "view": {...},
      "edit": {...},
      "delete": {...}
    }
  }
}
```

### Respuesta de Error
```json
{
  "success": false,
  "message": "Mensaje de error",
  "errors": {                // Para errores de validación
    "field": ["Error específico"]
  }
}
```

## 🔄 Ciclo de Vida de Componentes

### DataTable
1. **Mounted**: Carga metadatos y datos iniciales
2. **Filter Change**: Aplica filtros y recarga datos
3. **Sort Change**: Cambia ordenamiento y recarga datos
4. **Page Change**: Cambia página manteniendo filtros
5. **Action Click**: Emite evento con acción y item

### DataModal
1. **Open**: Valida configuración y carga metadatos
2. **Form Load**: Para edit/view, carga datos del item
3. **Submit**: Valida formulario y envía petición
4. **Success**: Emite evento y cierra modal
5. **Error**: Muestra errores de validación

## 🛡️ Seguridad y Permisos

### Verificación de Propiedad
```php
// En el modelo
public function canAccess($user): bool
{
    return $this->user_id === $user->id;
}

// En configuración
'permissions' => [
    'enabled' => true,
    'check_ownership' => true,
    'user_key' => 'user_id'
]
```

### Middleware y Validación
```php
// En rutas
Route::middleware(['auth', 'verified'])->group(function () {
    // Rutas protegidas
});

// En controlador
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email,' . $user->id
]);
```

## 🧪 Testing

### Testing de Backend
```php
// tests/Feature/DynamicUITest.php
class DynamicUITest extends TestCase
{
    public function test_can_list_users_with_metadata()
    {
        $response = $this->getJson('/api/users');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'current_page',
                        'total'
                    ]
                ]);
    }
    
    public function test_can_create_user_with_validation()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];
        
        $response = $this->postJson('/api/users', $userData);
        
        $response->assertStatus(201)
                ->assertJson(['success' => true]);
    }
}
```

### Testing de Frontend
```javascript
// tests/components/DataTable.test.js
import { mount } from '@vue/test-utils'
import MedaDataTable from '@/components/DataTable.vue'

describe('MedaDataTable', () => {
  it('loads data on mount', async () => {
    const wrapper = mount(MedaDataTable, {
      props: {
        endpoint: '/api/users',
        modelName: 'users'
      }
    })
    
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.loading).toBe(true)
    // Más assertions...
  })
})
```

## 🚀 Optimización y Performance

### Cache Strategies
1. **Metadata Cache**: Los metadatos se cachean por 1 hora por defecto
2. **Query Cache**: Consultas idénticas se cachean temporalmente
3. **Relationship Eager Loading**: Carga relaciones en una sola consulta

### Best Practices
1. **Lazy Loading**: Los componentes se cargan cuando se necesitan
2. **Debounced Search**: La búsqueda tiene delay para evitar múltiples requests
3. **Pagination**: Siempre usa paginación para grandes datasets
4. **Index Database**: Asegúrate de tener índices en campos filtrable/searchable

### Monitoring
```php
// Logs automáticos en storage/logs/dynamic-ui.log
Log::channel('dynamic-ui')->info('Query executed', [
    'model' => User::class,
    'query_time' => $queryTime,
    'result_count' => $resultCount
]);
```

---

Esta guía técnica proporciona toda la información necesaria para implementar, personalizar y mantener Dynamic UI en cualquier proyecto Laravel + Vue.js.