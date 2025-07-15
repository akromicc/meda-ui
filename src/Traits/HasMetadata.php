<?php

namespace Meda\DynamicUI\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasMetadata
{
    /**
     * Definir configuración de tabla
     * Sobrescribir en el modelo para personalizar
     */
    public function defineTable(): array
    {
        return [
            'columns' => $this->getDefaultColumns(),
            'options' => $this->getDefaultOptions(),
            'searchColumns' => $this->getDefaultSearchColumns(),
            'relations' => $this->getDefaultRelations()
        ];
    }

    /**
     * Definir configuración de modal
     * Sobrescribir en el modelo para personalizar
     */
    public function defineModal(): array
    {
        return [
            'fields' => $this->getDefaultFields(),
            'title' => $this->getModalTitle(),
            'submitText' => 'Guardar'
        ];
    }

    /**
     * Obtener columnas por defecto
     */
    protected function getDefaultColumns(): array
    {
        $table = $this->getTable();
        $columns = [];
        
        // Obtener columnas de la tabla
        $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        
        foreach ($tableColumns as $column) {
            $columns[] = [
                'key' => $column,
                'label' => ucfirst(str_replace('_', ' ', $column)),
                'type' => $this->getColumnType($column),
                'sortable' => true,
                'filterable' => true,
                'width' => $this->getColumnWidth($column)
            ];
        }

        return $columns;
    }

    /**
     * Obtener opciones por defecto
     */
    protected function getDefaultOptions(): array
    {
        return [
            'is_active' => [
                ['value' => 1, 'label' => 'Sí'],
                ['value' => 0, 'label' => 'No']
            ],
            'status' => [
                ['value' => 'active', 'label' => 'Activo'],
                ['value' => 'inactive', 'label' => 'Inactivo'],
                ['value' => 'pending', 'label' => 'Pendiente']
            ]
        ];
    }

    /**
     * Obtener columnas de búsqueda por defecto
     */
    protected function getDefaultSearchColumns(): array
    {
        $table = $this->getTable();
        return \Illuminate\Support\Facades\Schema::getColumnListing($table);
    }

    /**
     * Obtener relaciones por defecto
     */
    protected function getDefaultRelations(): array
    {
        return [];
    }

    /**
     * Obtener campos por defecto
     */
    protected function getDefaultFields(): array
    {
        $fillable = $this->getFillable();
        $fields = [];
        
        foreach ($fillable as $field) {
            $fields[] = [
                'key' => $field,
                'label' => ucfirst(str_replace('_', ' ', $field)),
                'type' => $this->getFieldType($field),
                'required' => $this->isFieldRequired($field),
                'placeholder' => "Ingrese " . str_replace('_', ' ', $field),
                'validation' => $this->getFieldValidation($field)
            ];
        }

        return $fields;
    }

    /**
     * Obtener título del modal
     */
    protected function getModalTitle(): string
    {
        $table = $this->getTable();
        return 'Crear ' . ucfirst(str_replace('_', ' ', $table));
    }

    /**
     * Obtener tipo de columna
     */
    protected function getColumnType(string $column): string
    {
        $casts = $this->getCasts();
        
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
    protected function getFieldType(string $field): string
    {
        $casts = $this->getCasts();
        
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
     * Verificar si un campo es requerido
     */
    protected function isFieldRequired(string $field): bool
    {
        $table = $this->getTable();
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
    protected function getFieldValidation(string $field): string
    {
        $rules = [];
        
        if ($this->isFieldRequired($field)) {
            $rules[] = 'required';
        }
        
        $casts = $this->getCasts();
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
     * Obtener clave de usuario para permisos
     */
    public function getUserKey(): string
    {
        return 'user_id';
    }

    /**
     * Obtener reglas de validación personalizadas
     */
    public function getValidationRules(string $field, $id = null): array
    {
        return [];
    }

    /**
     * Obtener reglas de unicidad
     */
    public function getUniqueRules(string $field, $id = null): ?string
    {
        $table = $this->getTable();
        
        if ($id) {
            return "unique:{$table},{$field},{$id}";
        }
        
        return "unique:{$table},{$field}";
    }

    /**
     * Verificar permisos de acceso
     */
    public function canAccess($user): bool
    {
        $userKey = $this->getUserKey();
        return $this->$userKey === $user->id;
    }
}