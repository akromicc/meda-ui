<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\QuerySortingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Closure;

class DeviceController extends Controller
{
    private $querySortingService;

    public function __construct()
    {
        $this->querySortingService = new QuerySortingService();
    }

    /**
     * Display a listing of the resource with optimized filtering, searching, and sorting.
     * 
     * Parámetros soportados:
     * - search: término de búsqueda
     * - sortBy: columna por la cual ordenar  
     * - order: dirección del ordenamiento (asc|desc)
     * - per_page: elementos por página
     * - f_status: filtrar por estado (connected, disconnected, qr_required)
     * - f_is_active: filtrar por dispositivos activos (true|false)
     * - f_created_at: filtrar por rango de fechas (ej: 2024-01-01_2024-01-31)
     * - f_user_id:user.name: filtrar por nombre de usuario relacionado (nueva sintaxis)
     * - f_user_id:user.email: filtrar por email de usuario relacionado
     * 
     * Ejemplo de URL: /api/devices?search=test&sortBy=name&order=asc&f_status=connected&per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $model = new Device();
        
        $relations = ['user'];
        
        $searchColumns = [
            'name',
            'phone_number', 
            'session_id',
            'r:user.name', // Buscar en la relación user por nombre
            'r:user.email' // Buscar en la relación user por email
        ];
        
        $customQuery = function ($query) {
            // Solo mostrar dispositivos del usuario autenticado
            $query->byUser(Auth::id());
        };

        $additionalData = [
            'stats' => [
                'total_devices' => Device::byUser(Auth::id())->count(),
                'connected_devices' => Device::byUser(Auth::id())->connected()->count(),
                'active_devices' => Device::byUser(Auth::id())->active()->count(),
            ]
        ];

        return $this->querySortingService->getJsonResponse(
            $model,
            $request,
            $relations,
            $searchColumns,
            $customQuery,
            $request->get('per_page', 15),
            'created_at',
            'desc',
            $additionalData
        );
    }

    /**
     * Store a newly created device in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'session_id' => 'nullable|string|max:255|unique:devices,session_id',
            'is_active' => 'boolean'
        ]);

        // Generate session_id if not provided
        $sessionId = $request->input('session_id') ?: 'device_' . Str::random(12);

        $device = Device::create([
            'name' => $request->input('name'),
            'session_id' => $sessionId,
            'status' => 'disconnected',
            'is_active' => $request->input('is_active', true),
            'user_id' => $request->user()->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo creado exitosamente',
            'data' => $device->load('user')
        ]);
    }

    /**
     * Display the specified device.
     */
    public function show(Device $device)
    {
        // Verify ownership
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este dispositivo'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $device->load('user')
        ]);
    }

    /**
     * Update the specified device in storage.
     */
    public function update(Request $request, Device $device)
    {
        // Verify ownership
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para modificar este dispositivo'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'session_id' => 'nullable|string|max:255|unique:devices,session_id,' . $device->id,
            'is_active' => 'boolean'
        ]);

        $device->update([
            'name' => $request->input('name'),
            'session_id' => $request->input('session_id') ?: $device->session_id,
            'is_active' => $request->input('is_active', $device->is_active)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo actualizado exitosamente',
            'data' => $device->load('user')
        ]);
    }

    /**
     * Remove the specified device from storage.
     */
    public function destroy(Device $device)
    {
        // Verify ownership
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar este dispositivo'
            ], 403);
        }

        $deviceName = $device->name;
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => "Dispositivo '{$deviceName}' eliminado exitosamente"
        ]);
    }

    /**
     * Obtener código QR para conectar
     */
    public function getQrCode(Device $device): JsonResponse
    {
        try {
            if ($device->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                ], 403);
            }

            if (!$device->needsQr()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El dispositivo no requiere código QR',
                ], 400);
            }

            // Generar código QR simulado para demostración
            $qrCode = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
            
            // Actualizar estado del dispositivo
            $device->update([
                'status' => 'qr_required',
                'qr_code' => $qrCode,
                'qr_expires_at' => now()->addMinutes(2)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Código QR generado exitosamente',
                'data' => [
                    'qr_code' => $qrCode,
                    'expires_at' => $device->qr_expires_at,
                    'session_id' => $device->session_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener código QR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar código QR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conectar dispositivo
     */
    public function connect(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        if ($device->status === 'connected') {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo ya está conectado',
                'data' => $device
            ], 400);
        }

        $device->markAsConnected();

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo conectado exitosamente',
            'data' => $device
        ]);
    }

    /**
     * Desconectar dispositivo
     */
    public function disconnect(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $device->markAsDisconnected('Desconectado manualmente');

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo desconectado exitosamente',
            'data' => $device
        ]);
    }

    /**
     * Obtener estadísticas del dispositivo
     */
    public function stats(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $stats = [
            'device' => $device,
            'status' => $device->status,
            'is_connected' => $device->isConnected(),
            'is_active' => $device->is_active,
            'created_at' => $device->created_at,
            'connected_at' => $device->connected_at,
            'last_seen' => $device->last_seen,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Buscar usuarios para asignar a dispositivos
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        
        if (empty($search)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $users = \App\Models\User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
