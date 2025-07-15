<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Meda\DynamicUI\Traits\HasMetadata;

/**
 * Ejemplo de implementación de Dynamic UI
 * 
 * Este es un ejemplo completo de cómo usar Dynamic UI
 * con un modelo User básico.
 */
class User extends Model
{
    use HasMetadata;

    protected $fillable = [
        'name', 
        'email', 
        'phone', 
        'role', 
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime'
    ];

    /**
     * Configuración de tabla para Dynamic UI
     * 
     * Define cómo se mostrará la tabla de usuarios
     */
    public function defineTable(): array
    {
        return [
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'type' => 'number',
                    'width' => '80px',
                    'sortable' => true
                ],
                [
                    'key' => 'name',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'sortable' => true,
                    'filterable' => true
                ],
                [
                    'key' => 'email',
                    'label' => 'Correo',
                    'type' => 'email',
                    'sortable' => true,
                    'filterable' => true
                ],
                [
                    'key' => 'phone',
                    'label' => 'Teléfono',
                    'type' => 'phone',
                    'sortable' => false,
                    'filterable' => true
                ],
                [
                    'key' => 'role',
                    'label' => 'Rol',
                    'type' => 'select',
                    'sortable' => true,
                    'filterable' => true
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Activo',
                    'type' => 'boolean',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '100px'
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Registrado',
                    'type' => 'date',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '150px'
                ]
            ],
            'options' => [
                'role' => [
                    ['value' => 'admin', 'label' => 'Administrador'],
                    ['value' => 'user', 'label' => 'Usuario'],
                    ['value' => 'moderator', 'label' => 'Moderador']
                ],
                'is_active' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No']
                ]
            ],
            'searchColumns' => ['name', 'email', 'phone'],
            'relations' => []
        ];
    }

    /**
     * Definir todos los modales disponibles
     * 
     * El sistema automáticamente separa "create" (botón principal) 
     * vs acciones de item (menú tres puntos)
     */
    public function defineModals(): array
    {
        // Campos compartidos para todos los modales
        $fields = [
            [
                'key' => 'name',
                'label' => 'Nombre Completo',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Ej: Juan Pérez',
                'validation' => 'required|string|max:255'
            ],
            [
                'key' => 'email',
                'label' => 'Correo Electrónico',
                'type' => 'email',
                'required' => true,
                'placeholder' => 'usuario@ejemplo.com',
                'validation' => 'required|email|unique:users,email'
            ],
            [
                'key' => 'phone',
                'label' => 'Teléfono',
                'type' => 'phone',
                'required' => false,
                'placeholder' => '+34 600 000 000',
                'validation' => 'nullable|string|max:20'
            ],
            [
                'key' => 'role',
                'label' => 'Rol del Usuario',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'user', 'label' => 'Usuario'],
                    ['value' => 'admin', 'label' => 'Administrador'],
                    ['value' => 'moderator', 'label' => 'Moderador']
                ],
                'defaultValue' => 'user',
                'validation' => 'required|in:user,admin,moderator'
            ],
            [
                'key' => 'is_active',
                'label' => 'Usuario Activo',
                'type' => 'boolean',
                'checkboxLabel' => 'Marcar como usuario activo',
                'defaultValue' => true,
                'validation' => 'boolean'
            ]
        ];

        return [
            // Botón principal (crear)
            'create' => [
                'key' => 'create',
                'label' => 'Nuevo Usuario',
                'icon' => 'fa fa-plus',
                'color' => 'primary',
                'type' => 'form',
                'fields' => $fields,
                'method' => 'POST'
            ],
            
            // Acciones del menú tres puntos
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
                'confirmMessage' => '¿Estás seguro de que quieres eliminar este usuario?',
                'method' => 'DELETE'
            ],
            
            // Acción personalizada
            'toggle_status' => [
                'key' => 'toggle_status',
                'label' => 'Activar/Desactivar',
                'icon' => 'fa fa-toggle-on',
                'color' => 'secondary',
                'type' => 'confirm',
                'condition' => 'role !== "admin"', // Se evalúa en frontend
                'confirmMessage' => '¿Cambiar el estado de este usuario?',
                'endpoint' => '/toggle-status', // Endpoint relativo
                'method' => 'POST'
            ]
        ];
    }

    /**
     * Estadísticas que se mostrarán en cards
     */
    public function defineStats(): array
    {
        return [
            'total_users' => [
                'label' => 'Total Usuarios',
                'icon' => 'fas fa-users',
                'color' => 'primary',
                'query' => function ($query) {
                    return $query->count();
                }
            ],
            'active_users' => [
                'label' => 'Usuarios Activos',
                'icon' => 'fas fa-user-check',
                'color' => 'success',
                'query' => function ($query) {
                    return $query->where('is_active', true)->count();
                }
            ],
            'admin_users' => [
                'label' => 'Administradores',
                'icon' => 'fas fa-user-shield',
                'color' => 'warning',
                'query' => function ($query) {
                    return $query->where('role', 'admin')->count();
                }
            ]
        ];
    }

    /**
     * Filtros personalizados
     */
    public function defineFilters(): array
    {
        return [
            'role_filter' => [
                'type' => 'select',
                'label' => 'Filtrar por Rol',
                'options' => [
                    ['value' => '', 'label' => 'Todos los roles'],
                    ['value' => 'admin', 'label' => 'Administradores'],
                    ['value' => 'user', 'label' => 'Usuarios'],
                    ['value' => 'moderator', 'label' => 'Moderadores']
                ]
            ],
            'status_filter' => [
                'type' => 'boolean',
                'label' => 'Solo Activos',
                'field' => 'is_active'
            ],
            'created_range' => [
                'type' => 'date_range',
                'label' => 'Fecha de Registro',
                'field' => 'created_at'
            ]
        ];
    }
}