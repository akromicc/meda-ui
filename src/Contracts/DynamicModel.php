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
     * Define todos los modales disponibles (view, edit, delete, create, custom)
     * El sistema automáticamente los separa en botón "crear" y menú de tres puntos
     * 
     * @return array
     */
    public function defineModals(): array;

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