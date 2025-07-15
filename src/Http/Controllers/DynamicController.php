<?php

namespace Meda\DynamicUI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Services\DynamicUIService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DynamicController extends Controller
{
    protected $dynamicUIService;
    protected $modelClass;
    protected $config;

    public function __construct(DynamicUIService $dynamicUIService)
    {
        $this->dynamicUIService = $dynamicUIService;
    }

    /**
     * Configurar el controlador dinámico
     */
    public function configure(string $modelClass, array $config = []): self
    {
        $this->modelClass = $modelClass;
        $this->config = $config;
        return $this;
    }

    /**
     * Mostrar lista de recursos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!$this->modelClass) {
                throw new \Exception('Modelo no configurado');
            }

            return $this->dynamicUIService->processDynamicView(
                $this->modelClass,
                $request,
                $this->config
            );

        } catch (\Exception $e) {
            Log::error('DynamicController Index Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar formulario para crear recurso
     */
    public function create(): JsonResponse
    {
        try {
            $metadata = $this->dynamicUIService->getModelMetadata($this->modelClass);
            
            return response()->json([
                'success' => true,
                'data' => $metadata['modal']
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Create Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener formulario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Guardar nuevo recurso
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $model = new $this->modelClass();
            
            // Validar datos
            $validationRules = $this->getValidationRules($model);
            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Aplicar datos del usuario si es necesario
            $data = $request->all();
            if (method_exists($model, 'getUserKey') && Auth::check()) {
                $data[$model->getUserKey()] = Auth::id();
            }

            // Crear modelo
            $item = $model::create($data);

            // Cargar relaciones si se especifican
            $relations = $this->config['relations'] ?? [];
            if (!empty($relations)) {
                $item->load($relations);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recurso creado exitosamente',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Store Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el recurso',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar recurso específico
     */
    public function show($id): JsonResponse
    {
        try {
            $model = new $this->modelClass();
            $item = $model::findOrFail($id);

            // Verificar permisos si es necesario
            if (!$this->canAccess($item)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este recurso'
                ], 403);
            }

            // Cargar relaciones
            $relations = $this->config['relations'] ?? [];
            if (!empty($relations)) {
                $item->load($relations);
            }

            return response()->json([
                'success' => true,
                'data' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Show Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el recurso',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar recurso
     */
    public function edit($id): JsonResponse
    {
        try {
            $model = new $this->modelClass();
            $item = $model::findOrFail($id);

            if (!$this->canAccess($item)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar este recurso'
                ], 403);
            }

            $metadata = $this->dynamicUIService->getModelMetadata($this->modelClass);
            $metadata['modal']['data'] = $item;

            return response()->json([
                'success' => true,
                'data' => $metadata['modal']
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Edit Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener formulario de edición',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar recurso específico
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $model = new $this->modelClass();
            $item = $model::findOrFail($id);

            if (!$this->canAccess($item)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para modificar este recurso'
                ], 403);
            }

            // Validar datos
            $validationRules = $this->getValidationRules($model, $id);
            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Actualizar modelo
            $item->update($request->all());

            // Cargar relaciones
            $relations = $this->config['relations'] ?? [];
            if (!empty($relations)) {
                $item->load($relations);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recurso actualizado exitosamente',
                'data' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el recurso',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar recurso específico
     */
    public function destroy($id): JsonResponse
    {
        try {
            $model = new $this->modelClass();
            $item = $model::findOrFail($id);

            if (!$this->canAccess($item)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este recurso'
                ], 403);
            }

            $itemName = $item->name ?? $item->id;
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => "Recurso '{$itemName}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Destroy Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el recurso',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener metadatos del modelo
     */
    public function metadata(): JsonResponse
    {
        try {
            $metadata = $this->dynamicUIService->getModelMetadata($this->modelClass);
            
            return response()->json([
                'success' => true,
                'data' => $metadata
            ]);

        } catch (\Exception $e) {
            Log::error('DynamicController Metadata Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener metadatos',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener reglas de validación
     */
    protected function getValidationRules(Model $model, $id = null): array
    {
        $rules = [];
        $fillable = $model->getFillable();

        foreach ($fillable as $field) {
            $fieldRules = [];

            // Reglas básicas
            if (method_exists($model, 'getValidationRules')) {
                $fieldRules = $model->getValidationRules($field, $id);
            } else {
                $fieldRules = $this->getDefaultValidationRules($field, $model, $id);
            }

            if (!empty($fieldRules)) {
                $rules[$field] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Obtener reglas de validación por defecto
     */
    protected function getDefaultValidationRules(string $field, Model $model, $id = null): string
    {
        $rules = [];
        
        // Verificar si es requerido
        if ($this->isFieldRequired($field, $model)) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Reglas por tipo de campo
        $casts = $model->getCasts();
        if (isset($casts[$field])) {
            switch ($casts[$field]) {
                case 'boolean':
                    $rules[] = 'boolean';
                    break;
                case 'integer':
                    $rules[] = 'integer';
                    break;
                case 'float':
                    $rules[] = 'numeric';
                    break;
                case 'datetime':
                case 'date':
                    $rules[] = 'date';
                    break;
            }
        }

        // Reglas específicas por nombre de campo
        if (str_contains($field, 'email')) {
            $rules[] = 'email';
        }

        if (str_contains($field, 'phone')) {
            $rules[] = 'string';
        }

        // Reglas de unicidad
        if (method_exists($model, 'getUniqueRules')) {
            $uniqueRules = $model->getUniqueRules($field, $id);
            if ($uniqueRules) {
                $rules[] = $uniqueRules;
            }
        }

        return implode('|', $rules);
    }

    /**
     * Verificar si un campo es requerido
     */
    protected function isFieldRequired(string $field, Model $model): bool
    {
        $table = $model->getTable();
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        
        foreach ($columns as $column) {
            if ($column === $field) {
                $columnInfo = \Illuminate\Support\Facades\Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableDetails($table)
                    ->getColumn($field);
                
                return !$columnInfo->getNotnull();
            }
        }
        
        return false;
    }

    /**
     * Verificar permisos de acceso
     */
    protected function canAccess($item): bool
    {
        // Si no hay usuario autenticado, permitir acceso
        if (!Auth::check()) {
            return true;
        }

        // Si el modelo tiene método de verificación de permisos
        if (method_exists($item, 'canAccess')) {
            return $item->canAccess(Auth::user());
        }

        // Verificar si el modelo pertenece al usuario
        if (method_exists($item, 'getUserKey')) {
            $userKey = $item->getUserKey();
            return $item->$userKey === Auth::id();
        }

        // Por defecto, permitir acceso
        return true;
    }
}