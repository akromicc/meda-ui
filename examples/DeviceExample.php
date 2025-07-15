<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Meda\DynamicUI\Traits\HasMetadata;

class Device extends Model
{
    use HasMetadata;

    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'status',
        'qr_code',
        'last_seen',
        'connected_at',
        'webhook_config',
        'is_active',
        'error_message',
        'retry_count',
        'device_info',
    ];

    protected $casts = [
        'qr_code' => 'array',
        'webhook_config' => 'array',
        'device_info' => 'array',
        'is_active' => 'boolean',
        'last_seen' => 'datetime',
        'connected_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeConnected($query)
    {
        return $query->where('status', 'connected');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Métodos auxiliares
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function needsQr(): bool
    {
        return $this->status === 'qr_required';
    }

    public function markAsConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'connected_at' => now(),
            'error_message' => null,
            'retry_count' => 0,
        ]);
    }

    public function markAsDisconnected(string $reason = null): void
    {
        $this->update([
            'status' => 'disconnected',
            'error_message' => $reason,
            'last_seen' => now(),
        ]);
    }

    // Configuración de tabla dinámica
    public function defineTable(): array
    {
        return [
            'columns' => [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'type' => 'number',
                    'sortable' => true,
                    'width' => '80px'
                ],
                [
                    'key' => 'name',
                    'label' => 'Nombre',
                    'type' => 'text',
                    'sortable' => true,
                    'filterable' => true
                ],
                [
                    'key' => 'phone_number',
                    'label' => 'Teléfono',
                    'type' => 'phone',
                    'sortable' => true,
                    'filterable' => true
                ],
                [
                    'key' => 'status',
                    'label' => 'Estado',
                    'type' => 'select',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '120px'
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
                    'key' => 'user.name',
                    'label' => 'Usuario',
                    'type' => 'search',
                    'filterable' => true,
                    'searchEndpoint' => '/api/search/users',
                    'filterField' => 'user_id'
                ],
                [
                    'key' => 'created_at',
                    'label' => 'Fecha Creación',
                    'type' => 'date',
                    'sortable' => true,
                    'filterable' => true,
                    'width' => '150px'
                ]
            ],
            'options' => [
                'status' => [
                    ['value' => 'connected', 'label' => 'Conectado'],
                    ['value' => 'disconnected', 'label' => 'Desconectado'],
                    ['value' => 'qr_required', 'label' => 'QR Requerido'],
                    ['value' => 'connecting', 'label' => 'Conectando'],
                    ['value' => 'error', 'label' => 'Error']
                ],
                'is_active' => [
                    ['value' => 1, 'label' => 'Sí'],
                    ['value' => 0, 'label' => 'No']
                ]
            ],
            'searchColumns' => [
                'name',
                'phone_number',
                'session_id',
                'user.name',
                'user.email'
            ],
            'relations' => ['user'],
            'actions' => [
                [
                    'key' => 'view',
                    'label' => 'Ver',
                    'icon' => 'fas fa-eye',
                    'color' => 'info'
                ],
                [
                    'key' => 'edit',
                    'label' => 'Editar',
                    'icon' => 'fas fa-edit',
                    'color' => 'primary'
                ],
                [
                    'key' => 'delete',
                    'label' => 'Eliminar',
                    'icon' => 'fas fa-trash',
                    'color' => 'danger'
                ],
                [
                    'key' => 'connect_whatsapp',
                    'label' => 'Conectar',
                    'icon' => 'fab fa-whatsapp',
                    'color' => 'success',
                    'condition' => function($item) {
                        return $item->status !== 'connected';
                    }
                ],
                [
                    'key' => 'disconnect_whatsapp',
                    'label' => 'Desconectar',
                    'icon' => 'fas fa-power-off',
                    'color' => 'warning',
                    'condition' => function($item) {
                        return $item->status === 'connected';
                    }
                ],
                [
                    'key' => 'view_qr',
                    'label' => 'Ver QR',
                    'icon' => 'fas fa-qrcode',
                    'color' => 'secondary',
                    'condition' => function($item) {
                        return $item->needsQr();
                    }
                ]
            ]
        ];
    }

    // Configuración de modal dinámico
    public function defineModal(): array
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Nombre del Dispositivo',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ej: Mi WhatsApp Business',
                    'validation' => 'required|string|max:255'
                ],
                [
                    'key' => 'phone_number',
                    'label' => 'Número de Teléfono',
                    'type' => 'phone',
                    'required' => false,
                    'placeholder' => '+1234567890',
                    'validation' => 'nullable|string|max:20'
                ],
                [
                    'key' => 'user_id',
                    'label' => 'Usuario Asignado',
                    'type' => 'search',
                    'required' => true,
                    'searchEndpoint' => '/api/search/users',
                    'validation' => 'required|exists:users,id'
                ],
                [
                    'key' => 'is_active',
                    'label' => 'Dispositivo Activo',
                    'type' => 'boolean',
                    'checkboxLabel' => 'Marcar como dispositivo activo',
                    'defaultValue' => true,
                    'validation' => 'boolean'
                ],
                [
                    'key' => 'webhook_config',
                    'label' => 'Configuración Webhook',
                    'type' => 'textarea',
                    'required' => false,
                    'placeholder' => '{"url": "https://example.com/webhook"}',
                    'description' => 'Configuración JSON para webhooks',
                    'validation' => 'nullable|json'
                ],
                [
                    'key' => 'qr_code',
                    'label' => 'Código QR',
                    'type' => 'qr',
                    'description' => 'Escanea este código QR con tu aplicación de WhatsApp',
                    'hideInForm' => true,
                    'hideInView' => false
                ],
                [
                    'key' => 'status',
                    'label' => 'Estado',
                    'type' => 'text',
                    'hideInForm' => true,
                    'hideInView' => false
                ],
                [
                    'key' => 'last_seen',
                    'label' => 'Última Actividad',
                    'type' => 'date',
                    'hideInForm' => true,
                    'hideInView' => false
                ]
            ],
            'title' => 'Crear Dispositivo',
            'submitText' => 'Guardar Dispositivo'
        ];
    }

    // Reglas de validación personalizadas
    public function getValidationRules(string $field, $id = null): array
    {
        $rules = [];

        switch ($field) {
            case 'name':
                $rules = ['required', 'string', 'max:255'];
                break;
            case 'phone_number':
                $rules = ['nullable', 'string', 'max:20'];
                break;
            case 'user_id':
                $rules = ['required', 'exists:users,id'];
                break;
            case 'is_active':
                $rules = ['boolean'];
                break;
            case 'webhook_config':
                $rules = ['nullable', 'json'];
                break;
        }

        return $rules;
    }

    // Reglas de unicidad
    public function getUniqueRules(string $field, $id = null): ?string
    {
        $table = $this->getTable();
        
        switch ($field) {
            case 'phone_number':
                if ($id) {
                    return "unique:{$table},phone_number,{$id}";
                }
                return "unique:{$table},phone_number";
            case 'session_id':
                if ($id) {
                    return "unique:{$table},session_id,{$id}";
                }
                return "unique:{$table},session_id";
        }

        return null;
    }

    // Clave de usuario para permisos
    public function getUserKey(): string
    {
        return 'user_id';
    }

    // Verificar permisos de acceso
    public function canAccess($user): bool
    {
        return $this->user_id === $user->id;
    }
}