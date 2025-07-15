<?php

namespace Meda\DynamicUI\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DynamicUIService
{
    protected $querySortingService;

    public function __construct(QuerySortingService $querySortingService)
    {
        $this->querySortingService = $querySortingService;
    }

    /**
     * Obtener metadatos de un modelo para la UI dinámica
     */
    public function getModelMetadata(string $modelClass, ?string $context = null): array
    {
        $cacheKey = "dynamic_ui_metadata_{$modelClass}_{$context}";
        
        return Cache::remember($cacheKey, config('dynamic-ui.cache_timeout', 3600), function () use ($modelClass, $context) {
            $model = new $modelClass();
            
            if (method_exists($model, 'defineTable')) {
                $tableConfig = $model->defineTable();
            } else {
                $tableConfig = $this->generateDefaultTableConfig($model);
            }

            if (method_exists($model, 'defineModals')) {
                $modalsConfig = $model->defineModals();
            } else {
                $modalsConfig = $this->generateDefaultModalsConfig($model);
            }

            // Separar modales en categorías
            $createModal = $modalsConfig['create'] ?? null;
            $itemActions = [];
            
            foreach ($modalsConfig as $key => $modal) {
                if ($key !== 'create') {
                    $itemActions[$key] = $modal;
                }
            }

            return [
                'table' => $tableConfig,
                'modals' => $modalsConfig,
                'createModal' => $createModal,
                'itemActions' => $itemActions,
                'model' => [
                    'name' => class_basename($modelClass),
                    'table' => $model->getTable(),
                    'fillable' => $model->getFillable(),
                    'casts' => $model->getCasts(),
                ]
            ];
        });
    }

    /**
     * Procesar vista dinámica con datos
     */
    public function processDynamicView(
        string $modelClass,
        Request $request,
        array $config = []
    ): JsonResponse {
        $model = new $modelClass();
        
        $relations = $config['relations'] ?? [];
        $searchColumns = $config['searchColumns'] ?? [];
        $customQuery = $config['customQuery'] ?? null;
        $perPage = $config['perPage'] ?? 15;
        $defaultSortBy = $config['defaultSortBy'] ?? 'created_at';
        $defaultOrder = $config['defaultOrder'] ?? 'desc';
        $additionalData = $config['additionalData'] ?? [];

        return $this->querySortingService->getJsonResponse(
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
    }

    /**
     * Generar configuración de tabla por defecto
     */
    protected function generateDefaultTableConfig(Model $model): array
    {
        $table = $model->getTable();
        $columns = [];
        
        // Obtener columnas de la tabla
        $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        
        foreach ($tableColumns as $column) {
            $columns[] = [
                'key' => $column,
                'label' => ucfirst(str_replace('_', ' ', $column)),
                'type' => $this->getColumnType($column, $model),
                'sortable' => true,
                'filterable' => true,
                'width' => $this->getColumnWidth($column)
            ];
        }

        return [
            'columns' => $columns,
            'options' => $this->getColumnOptions($model),
            'searchColumns' => $tableColumns,
            'relations' => []
        ];
    }

    /**
     * Generar configuración de modales por defecto
     */
    protected function generateDefaultModalsConfig(Model $model): array
    {
        $table = $model->getTable();
        $fillable = $model->getFillable();
        $fields = [];
        
        foreach ($fillable as $field) {
            $fields[] = [
                'key' => $field,
                'label' => ucfirst(str_replace('_', ' ', $field)),
                'type' => $this->getFieldType($field, $model),
                'required' => $this->isFieldRequired($field, $model),
                'placeholder' => "Ingrese " . str_replace('_', ' ', $field),
                'validation' => $this->getFieldValidation($field, $model)
            ];
        }

        $entityName = ucfirst(str_replace('_', ' ', $table));
        
        return [
            'create' => [
                'key' => 'create',
                'label' => "Crear {$entityName}",
                'icon' => 'fa fa-plus',
                'color' => 'primary',
                'type' => 'form',
                'fields' => $fields,
                'method' => 'POST'
            ],
            'view' => [
                'key' => 'view',
                'label' => 'Ver',
                'icon' => 'fa fa-eye',
                'color' => 'info',
                'type' => 'view',
                'fields' => $fields
            ],
            'edit' => [
                'key' => 'edit',
                'label' => 'Editar',
                'icon' => 'fa fa-edit',
                'color' => 'warning',
                'type' => 'form',
                'fields' => $fields,
                'method' => 'PUT'
            ],
            'delete' => [
                'key' => 'delete',
                'label' => 'Eliminar',
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'type' => 'confirm',
                'confirmMessage' => "¿Estás seguro de que quieres eliminar este {$entityName}?",
                'method' => 'DELETE'
            ]
        ];
    }

    /**
     * DEPRECATED: Generar configuración de modal por defecto
     * Mantenido por compatibilidad
     */
    protected function generateDefaultModalConfig(Model $model): array
    {
        $modals = $this->generateDefaultModalsConfig($model);
        return [
            'fields' => $modals['create']['fields'],
            'title' => $modals['create']['label'],
            'submitText' => 'Guardar'
        ];
    }

    /**
     * Obtener tipo de columna
     */
    protected function getColumnType(string $column, Model $model): string
    {
        $casts = $model->getCasts();
        
        if (isset($casts[$column])) {
            switch ($casts[$column]) {
                case 'boolean':
                    return 'boolean';
                case 'datetime':
                case 'date':
                    return 'date';
                case 'integer':
                case 'float':
                    return 'number';
                default:
                    return 'text';
            }
        }

        // Detectar por nombre de columna
        if (str_contains($column, 'email')) return 'email';
        if (str_contains($column, 'phone')) return 'phone';
        if (str_contains($column, 'url')) return 'url';
        if (str_contains($column, 'password')) return 'password';
        if (str_contains($column, 'image') || str_contains($column, 'photo')) return 'image';
        
        return 'text';
    }

    /**
     * Obtener tipo de campo
     */
    protected function getFieldType(string $field, Model $model): string
    {
        $casts = $model->getCasts();
        
        if (isset($casts[$field])) {
            switch ($casts[$field]) {
                case 'boolean':
                    return 'boolean';
                case 'datetime':
                case 'date':
                    return 'date';
                case 'integer':
                case 'float':
                    return 'number';
                default:
                    return 'text';
            }
        }

        // Detectar por nombre de campo
        if (str_contains($field, 'email')) return 'email';
        if (str_contains($field, 'phone')) return 'phone';
        if (str_contains($field, 'url')) return 'url';
        if (str_contains($field, 'password')) return 'password';
        if (str_contains($field, 'image') || str_contains($field, 'photo')) return 'file';
        if (str_contains($field, 'description') || str_contains($field, 'content')) return 'textarea';
        
        return 'text';
    }

    /**
     * Obtener ancho de columna
     */
    protected function getColumnWidth(string $column): string
    {
        if (in_array($column, ['id', 'status', 'is_active'])) {
            return '100px';
        }
        
        if (in_array($column, ['created_at', 'updated_at'])) {
            return '150px';
        }
        
        return 'auto';
    }

    /**
     * Obtener opciones de columna
     */
    protected function getColumnOptions(Model $model): array
    {
        $options = [];
        
        // Opciones para campos booleanos
        $options['is_active'] = [
            ['value' => 1, 'label' => 'Sí'],
            ['value' => 0, 'label' => 'No']
        ];
        
        $options['status'] = [
            ['value' => 'active', 'label' => 'Activo'],
            ['value' => 'inactive', 'label' => 'Inactivo'],
            ['value' => 'pending', 'label' => 'Pendiente']
        ];

        return $options;
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
     * Obtener validación de campo
     */
    protected function getFieldValidation(string $field, Model $model): string
    {
        $rules = [];
        
        if ($this->isFieldRequired($field, $model)) {
            $rules[] = 'required';
        }
        
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
        
        if (str_contains($field, 'email')) {
            $rules[] = 'email';
        }
        
        if (str_contains($field, 'phone')) {
            $rules[] = 'string';
        }
        
        return implode('|', $rules);
    }

    /**
     * Limpiar caché de metadatos
     */
    public function clearMetadataCache(string $modelClass): void
    {
        $cacheKey = "dynamic_ui_metadata_{$modelClass}";
        Cache::forget($cacheKey);
    }
}