<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasMetadata;

class Device extends Model
{
    use HasFactory, HasMetadata;

    protected $fillable = [
        'user_id',
        'session_id',
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

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
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

    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    public function resetRetryCount(): void
    {
        $this->update(['retry_count' => 0]);
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

    // Métodos de metadatos para meda-ui - SÚPER SIMPLE

    /**
     * Definir tabla completa - Limpia y óptima
     */
    public function defineTable(): array
    {
        return [
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'type' => 'number', 'sortable' => true, 'width' => '80px'],
                ['key' => 'name', 'label' => 'Nombre', 'type' => 'text', 'sortable' => true, 'filterable' => true],
                ['key' => 'phone_number', 'label' => 'Teléfono', 'type' => 'text', 'sortable' => true, 'filterable' => true],
                ['key' => 'status', 'label' => 'Estado', 'type' => 'select', 'sortable' => true, 'filterable' => true],
                ['key' => 'is_active', 'label' => 'Activo', 'type' => 'boolean', 'sortable' => true, 'filterable' => true],
                ['key' => 'user.name', 'label' => 'Usuario', 'type' => 'search', 'filterable' => true, 'searchEndpoint' => '/api/search/users', 'filterField' => 'user_id'],
                ['key' => 'created_at', 'label' => 'Fecha Creación', 'type' => 'date', 'sortable' => true, 'filterable' => true]
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
            'searchColumns' => ['name', 'phone_number', 'session_id', 'user.name', 'user.email'],
            'relations' => ['user']
        ];
    }

    /**
     * Definir modal completo - Solo un array simple
     */
    public function defineModal(): array
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Nombre del Dispositivo',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ej: Mi Dispositivo',
                    'validation' => 'required|string|max:255'
                ],
                [
                    'key' => 'session_id',
                    'label' => 'ID de Sesión',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Se generará automáticamente si no se especifica',
                    'validation' => 'nullable|string|max:255|unique:devices,session_id'
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
                    'key' => 'qr_code',
                    'label' => 'Código QR',
                    'type' => 'qr',
                    'description' => 'Código QR para conectar el dispositivo',
                    'hideInForm' => true,
                    'hideInView' => false
                ],
                [
                    'key' => 'status',
                    'label' => 'Estado',
                    'type' => 'text',
                    'hideInForm' => true,
                    'hideInView' => false
                ]
            ]
        ];
    }

    /**
     * Definir modales y acciones para los tres puntos
     */
    public function defineActionModals(): array
    {
        return [
            'view' => [
                'key' => 'view',
                'name' => 'view',
                'label' => 'Ver',
                'icon' => 'fa fa-eye',
                'color' => 'info',
                'type' => 'view',
            ],
            'edit' => [
                'key' => 'edit',
                'name' => 'edit',
                'label' => 'Editar',
                'icon' => 'fa fa-edit',
                'color' => 'warning',
                'type' => 'form',
            ],
            'delete' => [
                'key' => 'delete',
                'name' => 'delete',
                'label' => 'Eliminar',
                'icon' => 'fa fa-trash',
                'color' => 'danger',
                'type' => 'confirm',
                'confirmMessage' => '¿Estás seguro de que quieres eliminar este dispositivo? Esta acción no se puede deshacer.'
            ],
            'connect' => [
                'key' => 'connect',
                'name' => 'connect',
                'label' => 'Conectar',
                'icon' => 'fa fa-plug',
                'color' => 'success',
                'type' => 'confirm',
                'condition' => 'status !== "connected"',
                'confirmMessage' => '¿Estás seguro de que quieres conectar este dispositivo?'
            ],
            'disconnect' => [
                'key' => 'disconnect',
                'name' => 'disconnect',
                'label' => 'Desconectar',
                'icon' => 'fa fa-times',
                'color' => 'danger',
                'type' => 'confirm',
                'condition' => 'status === "connected"',
                'confirmMessage' => '¿Estás seguro de que quieres desconectar este dispositivo?'
            ],
            'view_qr' => [
                'key' => 'view_qr',
                'name' => 'view_qr',
                'label' => 'Ver QR',
                'icon' => 'fa fa-qrcode',
                'color' => 'info',
                'type' => 'view',
                'condition' => 'status === "qr_required"'
            ],

        ];
    }




}
