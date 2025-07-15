<?php

namespace Meda\DynamicUI\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Servicio de Rutas Automáticas
 * 
 * Maneja la resolución automática de modelos, operaciones y validaciones
 * para el sistema de rutas dinámicas.
 */
class AutoRouteService
{
    protected $registeredModels = [];
    protected $modelAliases = [];

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Registrar un modelo para uso automático
     */
    public function registerModel(string $modelClass, array $config = [], string $alias = null): void
    {
        $alias = $alias ?: $this->generateAlias($modelClass);
        
        $this->registeredModels[$alias] = [
            'class' => $modelClass,
            'config' => array_merge($this->getDefaultConfig(), $config)
        ];
        
        $this->modelAliases[strtolower($modelClass)] = $alias;
        $this->modelAliases[strtolower(class_basename($modelClass))] = $alias;
    }

    /**
     * Resolver el modelo desde el alias
     */
    public function resolveModel(string $modelAlias): ?string
    {
        $normalizedAlias = strtolower($modelAlias);
        
        // Buscar por alias directo
        if (isset($this->registeredModels[$normalizedAlias])) {
            return $this->registeredModels[$normalizedAlias]['class'];
        }
        
        // Buscar por alias inverso
        if (isset($this->modelAliases[$normalizedAlias])) {
            $alias = $this->modelAliases[$normalizedAlias];
            return $this->registeredModels[$alias]['class'];
        }
        
        // Auto-registrar si es un modelo válido
        if ($this->isValidModel($modelAlias)) {
            $modelClass = $this->buildModelClass($modelAlias);
            $this->registerModel($modelClass);
            return $modelClass;
        }
        
        return null;
    }

    /**
     * Resolver la operación basándose en la ruta y método HTTP
     */
    public function resolveOperation(Request $request, ?string $actionOrId, ?string $id): array
    {
        $method = $request->getMethod();
        
        // Si no hay parámetros adicionales
        if ($actionOrId === null) {
            return [
                'action' => $method === 'POST' ? 'store' : 'index',
                'params' => []
            ];
        }
        
        // Si es metadata
        if ($actionOrId === 'metadata') {
            return [
                'action' => 'metadata',
                'params' => []
            ];
        }
        
        // Si el segundo parámetro es un número (ID)
        if (is_numeric($actionOrId)) {
            $action = match($method) {
                'GET' => 'show',
                'PUT', 'PATCH' => 'update',
                'DELETE' => 'destroy',
                default => 'show'
            };
            
            return [
                'action' => $action,
                'params' => ['id' => $actionOrId]
            ];
        }
        
        // Si hay ID y acción personalizada
        if ($id !== null) {
            return [
                'action' => $actionOrId,
                'params' => ['id' => $id]
            ];
        }
        
        // Acción personalizada sin ID
        return [
            'action' => $actionOrId,
            'params' => []
        ];
    }

    /**
     * Obtener configuración del modelo
     */
    public function getModelConfig(string $modelClass): array
    {
        foreach ($this->registeredModels as $data) {
            if ($data['class'] === $modelClass) {
                return $data['config'];
            }
        }
        
        return $this->getDefaultConfig();
    }

    /**
     * Validar request automáticamente basado en metadatos
     */
    public function validateRequest(Request $request, string $modelClass, string $operation, $existingModel = null): void
    {
        $model = new $modelClass();
        
        if (!method_exists($model, 'getValidationRules')) {
            return; // No hay reglas de validación definidas
        }
        
        $rules = $model->getValidationRules($operation, $existingModel);
        
        if (empty($rules)) {
            return;
        }
        
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Manejar acciones personalizadas
     */
    public function handleCustomAction(Request $request, string $modelClass, string $action, $item = null): JsonResponse
    {
        // Buscar handlers registrados
        $handlerMethod = 'handle' . ucfirst($action) . 'Action';
        
        if (method_exists($this, $handlerMethod)) {
            return $this->$handlerMethod($request, $modelClass, $item);
        }
        
        // Acciones comunes predefinidas
        return match($action) {
            'toggle_status' => $this->handleToggleStatusAction($request, $modelClass, $item),
            'duplicate' => $this->handleDuplicateAction($request, $modelClass, $item),
            'export' => $this->handleExportAction($request, $modelClass, $item),
            'import' => $this->handleImportAction($request, $modelClass),
            'bulk_delete' => $this->handleBulkDeleteAction($request, $modelClass),
            'bulk_update' => $this->handleBulkUpdateAction($request, $modelClass),
            default => $this->handleGenericAction($request, $modelClass, $action, $item)
        };
    }

    /**
     * Cargar configuración desde archivo
     */
    protected function loadConfiguration(): void
    {
        $config = config('dynamic-ui.auto_routes', []);
        
        foreach ($config['models'] ?? [] as $alias => $modelData) {
            $this->registerModel($modelData['class'], $modelData['config'] ?? [], $alias);
        }
    }

    /**
     * Generar alias automático para el modelo
     */
    protected function generateAlias(string $modelClass): string
    {
        return strtolower(Str::plural(class_basename($modelClass)));
    }

    /**
     * Verificar si es un modelo válido
     */
    protected function isValidModel(string $modelAlias): bool
    {
        $modelClass = $this->buildModelClass($modelAlias);
        
        return class_exists($modelClass) && 
               is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class);
    }

    /**
     * Construir la clase del modelo desde el alias
     */
    protected function buildModelClass(string $modelAlias): string
    {
        $modelName = Str::studly(Str::singular($modelAlias));
        
        // Probar diferentes namespaces comunes
        $namespaces = [
            'App\\Models\\',
            'App\\',
            ''
        ];
        
        foreach ($namespaces as $namespace) {
            $fullClass = $namespace . $modelName;
            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }
        
        return 'App\\Models\\' . $modelName;
    }

    /**
     * Configuración por defecto
     */
    protected function getDefaultConfig(): array
    {
        return [
            'relations' => [],
            'searchColumns' => [],
            'perPage' => 15,
            'defaultSortBy' => 'created_at',
            'defaultOrder' => 'desc',
            'middleware' => [],
            'permissions' => []
        ];
    }

    /**
     * Acción: Cambiar estado
     */
    protected function handleToggleStatusAction(Request $request, string $modelClass, $item): JsonResponse
    {
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item requerido'], 400);
        }
        
        $statusField = $request->get('field', 'is_active');
        
        if (!in_array($statusField, $item->getFillable())) {
            return response()->json(['success' => false, 'message' => 'Campo no válido'], 400);
        }
        
        $item->update([$statusField => !$item->$statusField]);
        
        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $item
        ]);
    }

    /**
     * Acción: Duplicar
     */
    protected function handleDuplicateAction(Request $request, string $modelClass, $item): JsonResponse
    {
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item requerido'], 400);
        }
        
        $duplicate = $item->replicate();
        
        // Modificar campos únicos
        if (in_array('name', $item->getFillable())) {
            $duplicate->name = $item->name . ' (Copia)';
        }
        
        if (in_array('email', $item->getFillable()) && $item->email) {
            $duplicate->email = 'copia_' . time() . '_' . $item->email;
        }
        
        $duplicate->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Registro duplicado exitosamente',
            'data' => $duplicate
        ]);
    }

    /**
     * Acción: Exportar
     */
    protected function handleExportAction(Request $request, string $modelClass, $item = null): JsonResponse
    {
        $query = $modelClass::query();
        
        if ($item) {
            $query->where('id', $item->id);
        }
        
        $data = $query->get()->toArray();
        
        return response()->json([
            'success' => true,
            'message' => 'Exportación completada',
            'data' => $data,
            'filename' => strtolower(class_basename($modelClass)) . '_' . date('Y-m-d_H-i-s') . '.json'
        ]);
    }

    /**
     * Acción: Eliminar en lote
     */
    protected function handleBulkDeleteAction(Request $request, string $modelClass): JsonResponse
    {
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'IDs requeridos'], 400);
        }
        
        $deleted = $modelClass::whereIn('id', $ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => "{$deleted} registros eliminados exitosamente"
        ]);
    }

    /**
     * Acción: Actualizar en lote
     */
    protected function handleBulkUpdateAction(Request $request, string $modelClass): JsonResponse
    {
        $ids = $request->get('ids', []);
        $data = $request->get('data', []);
        
        if (empty($ids) || empty($data)) {
            return response()->json(['success' => false, 'message' => 'IDs y datos requeridos'], 400);
        }
        
        $updated = $modelClass::whereIn('id', $ids)->update($data);
        
        return response()->json([
            'success' => true,
            'message' => "{$updated} registros actualizados exitosamente"
        ]);
    }

    /**
     * Acción genérica
     */
    protected function handleGenericAction(Request $request, string $modelClass, string $action, $item = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "Acción '{$action}' no implementada"
        ], 501);
    }

    /**
     * Obtener modelos registrados
     */
    public function getRegisteredModels(): array
    {
        return $this->registeredModels;
    }

    /**
     * Limpiar registros (útil para testing)
     */
    public function clearRegistrations(): void
    {
        $this->registeredModels = [];
        $this->modelAliases = [];
    }
}