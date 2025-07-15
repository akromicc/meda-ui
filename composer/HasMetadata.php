<?php

namespace App\Models\Traits;

/**
 * Trait HasMetadata
 * 
 * Sistema súper simple para metadatos de tablas y modales.
 * Solo marca que el modelo tiene metadatos.
 * Los métodos defineTable() y defineModal() se definen en cada modelo.
 */
trait HasMetadata
{
    /**
     * Verificar si el modelo tiene metadatos de tabla
     */
    public function hasTableMetadata(): bool
    {
        return method_exists($this, 'defineTable');
    }

    /**
     * Verificar si el modelo tiene metadatos de modal
     */
    public function hasModalMetadata(): bool
    {
        return method_exists($this, 'defineModal');
    }

    /**
     * Obtener el nombre de la tabla para metadatos
     */
    public function getMetadataTableName(): string
    {
        return $this->getTable();
    }

    /**
     * Obtener el nombre del modelo para metadatos
     */
    public function getMetadataModelName(): string
    {
        return class_basename($this);
    }
}