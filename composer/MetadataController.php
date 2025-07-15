<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

class MetadataController extends Controller
{
    /**
     * Obtener metadatos de tabla para un modelo específico
     */
    public function getTableMetadata(Request $request, string $model): JsonResponse
    {
        if (App::environment('local', 'development')) {
            $modelInstance = $this->getModelInstance($model);
            if (!$modelInstance || !method_exists($modelInstance, 'defineTable')) {
                return response()->json([
                    'success' => false,
                    'message' => "Modelo {$model} no encontrado o no tiene metadatos de tabla"
                ], 404);
            }
            $definition = $modelInstance->defineTable();
            $actions = method_exists($modelInstance, 'defineActionModals') ? array_values($modelInstance->defineActionModals()) : [];
            $metadata = array_merge($definition, [
                'endpoint' => "/api/{$model}",
                'actions' => $actions,
                'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $model)),
                'searchPlaceholder' => $definition['searchPlaceholder'] ?? "Buscar {$model}...",
                'perPageOptions' => $definition['perPageOptions'] ?? [10, 15, 25, 50, 100],
                'defaultPerPage' => $definition['defaultPerPage'] ?? 15,
            ]);
        } else {
            $cacheKey = "metadata.table.{$model}";
            $metadata = Cache::remember($cacheKey, 3600, function () use ($model) {
                $modelInstance = $this->getModelInstance($model);
                if (!$modelInstance || !method_exists($modelInstance, 'defineTable')) {
                    return null;
                }
                $definition = $modelInstance->defineTable();
                $actions = method_exists($modelInstance, 'defineActionModals') ? array_values($modelInstance->defineActionModals()) : [];
                return array_merge($definition, [
                    'endpoint' => "/api/{$model}",
                    'actions' => $actions,
                'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $model)),
                'searchPlaceholder' => $definition['searchPlaceholder'] ?? "Buscar {$model}...",
                'perPageOptions' => $definition['perPageOptions'] ?? [10, 15, 25, 50, 100],
                'defaultPerPage' => $definition['defaultPerPage'] ?? 15,
            ]);
        });
        }
        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => "Modelo {$model} no encontrado o no tiene metadatos de tabla"
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $metadata
        ]);
    }
    
    /**
     * Obtener metadatos de modal para un modelo específico
     */
    public function getModalMetadata(Request $request, string $model): JsonResponse
    {
        if (App::environment('local', 'development')) {
            $modelInstance = $this->getModelInstance($model);
            if (!$modelInstance || !method_exists($modelInstance, 'defineModal')) {
                return response()->json([
                    'success' => false,
                    'message' => "Modelo {$model} no encontrado o no tiene metadatos de modal"
                ], 404);
            }
            $definition = $modelInstance->defineModal();
            $modalData = array_merge($definition, [
                'endpoint' => "/api/{$model}",
                'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $model)),
                'createTitle' => $definition['createTitle'] ?? "Crear " . Str::title(str_replace('_', ' ', $model)),
                'editTitle' => $definition['editTitle'] ?? "Editar " . Str::title(str_replace('_', ' ', $model)),
                'deleteTitle' => $definition['deleteTitle'] ?? "Eliminar " . Str::title(str_replace('_', ' ', $model)),
                'messages' => array_merge([
                    'created' => 'Elemento creado exitosamente',
                    'updated' => 'Elemento actualizado exitosamente', 
                    'deleted' => 'Elemento eliminado exitosamente'
                ], $definition['messages'] ?? [])
            ]);
            if (method_exists($modelInstance, 'defineActionModals')) {
                $actionModals = $modelInstance->defineActionModals();
                $modalData['actionModals'] = $actionModals;
            }
            $metadata = $modalData;
        } else {
            $cacheKey = "metadata.modal.{$model}";
            $metadata = Cache::remember($cacheKey, 3600, function () use ($model) {
                $modelInstance = $this->getModelInstance($model);
                if (!$modelInstance || !method_exists($modelInstance, 'defineModal')) {
                    return null;
                }
                $definition = $modelInstance->defineModal();
                $modalData = array_merge($definition, [
                    'endpoint' => "/api/{$model}",
                    'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $model)),
                    'createTitle' => $definition['createTitle'] ?? "Crear " . Str::title(str_replace('_', ' ', $model)),
                    'editTitle' => $definition['editTitle'] ?? "Editar " . Str::title(str_replace('_', ' ', $model)),
                    'deleteTitle' => $definition['deleteTitle'] ?? "Eliminar " . Str::title(str_replace('_', ' ', $model)),
                    'messages' => array_merge([
                        'created' => 'Elemento creado exitosamente',
                        'updated' => 'Elemento actualizado exitosamente', 
                        'deleted' => 'Elemento eliminado exitosamente'
                    ], $definition['messages'] ?? [])
                ]);
                if (method_exists($modelInstance, 'defineActionModals')) {
                    $actionModals = $modelInstance->defineActionModals();
                    $modalData['actionModals'] = $actionModals;
                }
                return $modalData;
            });
        }
        if (!$metadata) {
            return response()->json([
                'success' => false,
                'message' => "Modelo {$model} no encontrado o no tiene metadatos de modal"
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $metadata
        ]);
    }
    
    /**
     * Obtener todos los metadatos de una vez (optimización)
     */
    public function getAllMetadata(Request $request): JsonResponse
    {
        $models = $this->getAvailableModels();
        $tables = [];
        $modals = [];
        
        foreach ($models as $model) {
            $modelKey = Str::plural(Str::snake($model));
            
            // Metadatos de tabla
            $tableMetadata = Cache::get("metadata.table.{$modelKey}");
            if (!$tableMetadata) {
                $modelInstance = $this->getModelInstance($modelKey);
                if ($modelInstance && method_exists($modelInstance, 'defineTable')) {
                    $definition = $modelInstance->defineTable();
                    $tableMetadata = array_merge($definition, [
                        'endpoint' => "/api/{$modelKey}",
                        'actions' => $definition['actions'] ?? $this->getDefaultActions(),
                        'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $modelKey)),
                        'searchPlaceholder' => $definition['searchPlaceholder'] ?? "Buscar {$modelKey}...",
                        'perPageOptions' => $definition['perPageOptions'] ?? [10, 15, 25, 50, 100],
                        'defaultPerPage' => $definition['defaultPerPage'] ?? 15,
                    ]);
                    Cache::put("metadata.table.{$modelKey}", $tableMetadata, 3600);
                }
            }
            
            // Metadatos de modal
            $modalMetadata = Cache::get("metadata.modal.{$modelKey}");
            if (!$modalMetadata) {
                $modelInstance = $this->getModelInstance($modelKey);
                if ($modelInstance && method_exists($modelInstance, 'defineModal')) {
                    $definition = $modelInstance->defineModal();
                    $modalMetadata = array_merge($definition, [
                        'endpoint' => "/api/{$modelKey}",
                        'title' => $definition['title'] ?? Str::title(str_replace('_', ' ', $modelKey)),
                        'createTitle' => $definition['createTitle'] ?? "Crear " . Str::title(str_replace('_', ' ', $modelKey)),
                        'editTitle' => $definition['editTitle'] ?? "Editar " . Str::title(str_replace('_', ' ', $modelKey)),
                        'deleteTitle' => $definition['deleteTitle'] ?? "Eliminar " . Str::title(str_replace('_', ' ', $modelKey)),
                        'messages' => array_merge([
                            'created' => 'Elemento creado exitosamente',
                            'updated' => 'Elemento actualizado exitosamente', 
                            'deleted' => 'Elemento eliminado exitosamente'
                        ], $definition['messages'] ?? [])
                    ]);
                    Cache::put("metadata.modal.{$modelKey}", $modalMetadata, 3600);
                }
            }
            
            if ($tableMetadata) $tables[$modelKey] = $tableMetadata;
            if ($modalMetadata) $modals[$modelKey] = $modalMetadata;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'tables' => $tables,
                'modals' => $modals
            ]
        ]);
    }
    
    /**
     * Limpiar cache de metadatos
     */
    public function clearCache(Request $request): JsonResponse
    {
        $models = $this->getAvailableModels();
        
        foreach ($models as $model) {
            $modelKey = Str::plural(Str::snake($model));
            Cache::forget("metadata.table.{$modelKey}");
            Cache::forget("metadata.modal.{$modelKey}");
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Cache de metadatos limpiado exitosamente'
        ]);
    }
    
    /**
     * Obtener lista de modelos disponibles con metadatos
     */
    public function getAvailableModels(): array
    {
        $modelPath = app_path('Models');
        $models = [];
        
        if (!File::exists($modelPath)) {
            return $models;
        }
        
        $files = File::allFiles($modelPath);
        
        foreach ($files as $file) {
            $className = $file->getFilenameWithoutExtension();
            $fullClassName = "App\\Models\\{$className}";
            
            if (class_exists($fullClassName)) {
                try {
                    $reflection = new \ReflectionClass($fullClassName);
                    
                    // Verificar que tenga el trait HasMetadata
                    if ($this->hasMetadataTrait($reflection)) {
                        $models[] = $className;
                    }
                } catch (\Exception $e) {
                    // Ignorar errores de reflexión
                    continue;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Verificar si una clase tiene el trait HasMetadata
     */
    private function hasMetadataTrait(\ReflectionClass $reflection): bool
    {
        $traits = $reflection->getTraitNames();
        return in_array('App\\Models\\Traits\\HasMetadata', $traits);
    }
    
    /**
     * Obtener instancia del modelo
     */
    private function getModelInstance(string $model)
    {
        // Convertir plural a singular para el nombre de clase
        $className = Str::studly(Str::singular($model));
        $fullClassName = "App\\Models\\{$className}";
        
        if (!class_exists($fullClassName)) {
            return null;
        }
        
        try {
            return new $fullClassName();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener acciones por defecto
     */
    private function getDefaultActions(): array
    {
        return [
            [
                'name' => 'view',
                'label' => 'Ver',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>',
                'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300'
            ],
            [
                'name' => 'edit',
                'label' => 'Editar',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
                'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300'
            ],
            [
                'name' => 'delete',
                'label' => 'Eliminar',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>',
                'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300'
            ]
        ];
    }
}