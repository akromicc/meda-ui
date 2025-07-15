<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Meda\DynamicUI\Traits\HasMetadata;

/**
 * Modelo de ejemplo usando Dynamic UI
 * 
 * Este ejemplo muestra cómo definir modales personalizados
 * que generan acciones automáticamente en la UI.
 */
class UserModel extends Model
{
    use HasFactory, HasMetadata;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'password'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Definir configuración de tabla personalizada
     */
    public function defineTable(): array
    {
        return [
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'type' => 'number',
                    'sortable' => true,
                    'filterable' => false,
                    'width' => '80px'
                ],
                [
                    'key' => 'name',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '200px'
                ],
                [
                    'key' => 'email',
                    'label' => 'Email',
                    'type' => 'email',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '250px'
                ],
                [
                    'key' => 'phone',
                    'label' => 'Teléfono',
                    'type' => 'phone',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '150px'
                ],
                [
                    'key' => 'role',
                    'label' => 'Rol',
                    'type' => 'select',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '120px'
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Estado',
                    'type' => 'boolean',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '100px'
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Creado',
                    'type' => 'date',
                    'sortable' => true,
                    'filterable' => false,
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
                    ['value' => 1, 'label' => 'Activo'],
                    ['value' => 0, 'label' => 'Inactivo']
                ]
            ],
            'searchColumns' => ['name', 'email', 'phone'],
            'relations' => []
        ];
    }

    /**
     * Definir todos los modales (las acciones se generan automáticamente)
     */
    public function defineModals(): array
    {
        return [
            'create' => [
                'key' => 'create',
                'label' => 'Crear Usuario',
                'icon' => 'fa fa-plus',
                'color' => 'primary',
                'type' => 'form',
                'fields' => $this->getDefaultFields(),
                'method' => 'POST',
                'showAsButton' => true,
                'showInDropdown' => false
            ],
            'view' => [
                'key' => 'view',
                'label' => 'Ver Usuario',
                'icon' => 'fa fa-eye',
                'color' => 'info',
                'type' => 'view',
                'fields' => $this->getDefaultFields(),
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'edit' => [
                'key' => 'edit',
                'label' => 'Editar Usuario',
                'icon' => 'fa fa-edit',
                'color' => 'warning',
                'type' => 'form',
                'fields' => $this->getDefaultFields(),
                'method' => 'PUT',
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'delete' => [
                'key' => 'delete',
                'label' => 'Eliminar Usuario',
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'type' => 'confirm',
                'confirmMessage' => '¿Estás seguro de que quieres eliminar este usuario?',
                'method' => 'DELETE',
                'showInDropdown' => true,
                'showAsButton' => false
            ]
        ];
    }

    /**
     * Definir acciones personalizadas adicionales
     */
    public function defineCustomActions(): array
    {
        return [
            'toggle_status' => [
                'key' => 'toggle_status',
                'label' => 'Cambiar Estado',
                'icon' => 'fa fa-toggle-on',
                'color' => 'success',
                'type' => 'confirm',
                'confirmMessage' => '¿Estás seguro de que quieres cambiar el estado de este usuario?',
                'method' => 'POST',
                'endpoint' => '/api/users/{id}/toggle-status',
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'duplicate' => [
                'key' => 'duplicate',
                'label' => 'Duplicar',
                'icon' => 'fa fa-copy',
                'color' => 'secondary',
                'type' => 'confirm',
                'confirmMessage' => '¿Estás seguro de que quieres duplicar este usuario?',
                'method' => 'POST',
                'endpoint' => '/api/users/{id}/duplicate',
                'showInDropdown' => true,
                'showAsButton' => false
            ],
            'export' => [
                'key' => 'export',
                'label' => 'Exportar',
                'icon' => 'fa fa-download',
                'color' => 'success',
                'type' => 'download',
                'method' => 'GET',
                'endpoint' => '/api/users/export',
                'showInDropdown' => true,
                'showAsButton' => false
            ]
        ];
    }

    /**
     * Obtener campos por defecto para formularios
     */
    protected function getDefaultFields(): array
    {
        return [
            [
                'key' => 'name',
                'label' => 'Nombre',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Ingrese el nombre completo',
                'validation' => 'required|min:2|max:100'
            ],
            [
                'key' => 'email',
                'label' => 'Email',
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
                'placeholder' => '+1234567890',
                'validation' => 'nullable|phone'
            ],
            [
                'key' => 'role',
                'label' => 'Rol',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'user', 'label' => 'Usuario'],
                    ['value' => 'moderator', 'label' => 'Moderador'],
                    ['value' => 'admin', 'label' => 'Administrador']
                ],
                'validation' => 'required|in:user,moderator,admin'
            ],
            [
                'key' => 'is_active',
                'label' => 'Usuario Activo',
                'type' => 'boolean',
                'required' => false,
                'checkboxLabel' => 'Marcar si el usuario está activo',
                'validation' => 'boolean'
            ],
            [
                'key' => 'password',
                'label' => 'Contraseña',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Dejar vacío para mantener la actual',
                'help' => 'Solo llenar si quieres cambiar la contraseña',
                'validation' => 'nullable|min:6'
            ]
        ];
    }
}