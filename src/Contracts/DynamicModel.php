<?php

namespace DynamicUI\Contracts;

interface DynamicModel
{
    /**
     * Define la configuración de la tabla para Dynamic UI
     * 
     * @return array
     */
    public function defineTable(): array;

    /**
     * Define la configuración del modal/formulario para Dynamic UI
     * 
     * @return array
     */
    public function defineModal(): array;

    /**
     * Define las acciones disponibles para cada item (view, edit, delete, custom actions)
     * 
     * @return array
     */
    public function defineActionModals(): array;

    /**
     * Define filtros personalizados para la tabla
     * 
     * @return array
     */
    public function defineFilters(): array;

    /**
     * Define estadísticas que se mostrarán en cards
     * 
     * @return array
     */
    public function defineStats(): array;
}