<?php

/**
 * Ejemplos de Configuración de Rutas Automáticas
 * 
 * Este archivo muestra diferentes formas de configurar rutas dinámicas
 * en Dynamic UI para crear aplicaciones súper rápidamente.
 */

// routes/api.php

use Meda\DynamicUI\Http\Controllers\AutoDynamicController;
use Meda\DynamicUI\Services\AutoRouteService;

/*
|--------------------------------------------------------------------------
| OPCIÓN 1: RUTA UNIVERSAL AUTOMÁTICA (MÁS SIMPLE)
|--------------------------------------------------------------------------
|
| Una sola ruta maneja todos los modelos automáticamente.
| No necesitas escribir controladores ni definir rutas por separado.
|
*/

// Ruta universal que maneja TODOS los modelos dinámicamente
Route::any('/dynamic/{model}/{action?}/{id?}', [AutoDynamicController::class, 'handle'])
    ->middleware(['api'])
    ->where(['model' => '[a-zA-Z_]+', 'action' => '[a-zA-Z0-9_]+', 'id' => '[0-9]+']);

/*
Esto automáticamente crea estas rutas para CUALQUIER modelo:

GET    /dynamic/users           -> listar usuarios
POST   /dynamic/users           -> crear usuario
GET    /dynamic/users/123       -> mostrar usuario 123
PUT    /dynamic/users/123       -> actualizar usuario 123
DELETE /dynamic/users/123       -> eliminar usuario 123
GET    /dynamic/users/metadata  -> metadatos de usuario
POST   /dynamic/users/123/toggle_status -> acción personalizada

Y lo mismo para products, orders, categories, etc.
*/

/*
|--------------------------------------------------------------------------
| OPCIÓN 2: REGISTRO MANUAL CON MÁS CONTROL
|--------------------------------------------------------------------------
|
| Si quieres más control, puedes registrar modelos específicos.
|
*/

Route::prefix('api/v2')->group(function () {
    $autoRoute = app(AutoRouteService::class);
    
    // Registrar modelos específicos con configuración
    $autoRoute->registerModel(\App\Models\User::class, [
        'relations' => ['profile', 'roles'],
        'searchColumns' => ['name', 'email', 'profile.company'],
        'permissions' => ['users.manage']
    ], 'usuarios'); // Alias en español
    
    $autoRoute->registerModel(\App\Models\Product::class, [
        'relations' => ['category', 'images'],
        'searchColumns' => ['name', 'description', 'sku'],
        'perPage' => 20
    ], 'productos');
    
    // Crear rutas para modelos registrados
    Route::any('/dynamic/{model}/{action?}/{id?}', [AutoDynamicController::class, 'handle']);
});

/*
|--------------------------------------------------------------------------
| OPCIÓN 3: HÍBRIDO - AUTOMÁTICO + PERSONALIZADO
|--------------------------------------------------------------------------
|
| Combina rutas automáticas con endpoints personalizados cuando necesites
| lógica específica.
|
*/

Route::prefix('api')->group(function () {
    // Rutas automáticas para la mayoría de operaciones
    Route::any('/auto/{model}/{action?}/{id?}', [AutoDynamicController::class, 'handle']);
    
    // Endpoints personalizados para lógica específica
    Route::prefix('users')->group(function () {
        Route::post('/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/bulk-invite', [UserController::class, 'bulkInvite']);
        Route::get('/dashboard-stats', [UserController::class, 'dashboardStats']);
    });
});

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN EN EL MODELO
|--------------------------------------------------------------------------
|
| Solo necesitas configurar el modelo con metadatos. 
| TODO lo demás se genera automáticamente.
|
*/

// app/Models/Product.php
/*
<?php

use Meda\DynamicUI\Traits\HasMetadata;

class Product extends Model
{
    use HasMetadata;
    
    protected $fillable = ['name', 'price', 'category_id', 'is_active'];

    // Opcional: Solo si quieres personalizar
    public function defineModals(): array
    {
        return [
            'create' => [
                'label' => 'Nuevo Producto',
                'fields' => $this->getDefaultFields()
            ],
            'edit' => ['label' => 'Editar Producto'],
            'delete' => ['confirmMessage' => '¿Eliminar este producto?'],
            'duplicate' => [
                'label' => 'Duplicar',
                'type' => 'confirm',
                'confirmMessage' => '¿Duplicar este producto?'
            ]
        ];
    }

    // Opcional: Validación automática
    public function getValidationRules(string $operation, $existingModel = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id'
        ];
        
        // Reglas específicas por operación
        if ($operation === 'update' && $existingModel) {
            $rules['name'] .= '|unique:products,name,' . $existingModel->id;
        } else {
            $rules['name'] .= '|unique:products,name';
        }
        
        return $rules;
    }

    // Opcional: Acción personalizada
    public function handleDuplicate(Request $request)
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Copia)';
        $duplicate->save();
        
        return $duplicate;
    }
}
*/

/*
|--------------------------------------------------------------------------
| MIDDLEWARE PERSONALIZADO (OPCIONAL)
|--------------------------------------------------------------------------
|
| Si necesitas lógica específica de autenticación o permisos.
|
*/

// app/Http/Middleware/DynamicUIPermissions.php
/*
<?php

class DynamicUIPermissions
{
    public function handle($request, Closure $next)
    {
        $model = $request->route('model');
        $action = $this->resolveAction($request);
        
        // Verificar permisos dinámicamente
        $permission = "{$model}.{$action}";
        
        if (!auth()->user()->can($permission)) {
            return response()->json(['message' => 'Sin permisos'], 403);
        }
        
        return $next($request);
    }
}
*/

/*
|--------------------------------------------------------------------------
| PROVEEDORES DE SERVICIO (OPCIONAL)
|--------------------------------------------------------------------------
|
| Para registro automático en el boot de la aplicación.
|
*/

// app/Providers/DynamicUIServiceProvider.php
/*
<?php

class DynamicUIServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $autoRoute = $this->app->make(AutoRouteService::class);
        
        // Auto-registrar todos los modelos en App\Models\
        $this->autoRegisterModels($autoRoute);
    }
    
    private function autoRegisterModels(AutoRouteService $autoRoute)
    {
        $modelsPath = app_path('Models');
        $models = glob($modelsPath . '/*.php');
        
        foreach ($models as $modelFile) {
            $modelName = basename($modelFile, '.php');
            $modelClass = "App\\Models\\{$modelName}";
            
            if (class_exists($modelClass) && 
                in_array(HasMetadata::class, class_uses_recursive($modelClass))) {
                $autoRoute->registerModel($modelClass);
            }
        }
    }
}
*/

/*
|--------------------------------------------------------------------------
| EJEMPLOS DE USO EN EL FRONTEND
|--------------------------------------------------------------------------
*/

// resources/js/router/index.js
/*
const routes = [
    // Ruta universal para cualquier modelo
    {
        path: '/admin/:model',
        component: () => import('@/views/DynamicView.vue'),
        props: true,
        meta: { requiresAuth: true }
    },
    
    // Rutas específicas si necesitas personalización
    {
        path: '/admin/users',
        component: () => import('@/views/UsersView.vue'),
        meta: { model: 'users' }
    }
];
*/

// resources/js/views/DynamicView.vue
/*
<template>
  <DynamicView 
    :base-path="'/dynamic'"
    :use-auto-routes="true"
    @action="handleCustomAction"
  >
    <!-- Personalización específica de celdas -->
    <template #custom-slots>
      <div slot-name="cell-price" slot-component="PriceCell" />
      <div slot-name="cell-status" slot-component="StatusBadge" />
    </template>
  </DynamicView>
</template>

<script>
import DynamicView from '@meda/dynamic-ui/components/DynamicView.vue'

export default {
  components: { DynamicView },
  
  methods: {
    handleCustomAction({ action, item }) {
      if (action === 'custom_report') {
        this.generateReport(item)
      }
    }
  }
}
</script>
*/

/*
|--------------------------------------------------------------------------
| RESULTADO FINAL
|--------------------------------------------------------------------------
|
| Con esta configuración:
|
| ✅ Creas una aplicación CRUD completa en minutos
| ✅ No escribes controladores repetitivos
| ✅ No defines rutas una por una
| ✅ Validación automática
| ✅ Permisos automáticos
| ✅ UI generada automáticamente
| ✅ Personalización cuando la necesites
|
| CREAR UNA NUEVA ENTIDAD:
| 1. Crear el modelo con HasMetadata trait
| 2. Definir $fillable
| 3. ¡YA ESTÁ! Todo funciona automáticamente
|
| Para personalizar:
| - defineModals() en el modelo
| - Slots en el frontend
| - Middleware específico
| - Acciones personalizadas
|
*/