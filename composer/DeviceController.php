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
use Illuminate\Support\Facades\Http;
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

            $whatsappServerUrl = config('app.whatsapp_server_url', 'http://localhost:3000');
            
            // Usar el nuevo endpoint que combina creación y QR (más rápido)
            $response = Http::timeout(5)->post($whatsappServerUrl . '/session/create-and-qr', [
                'sessionId' => $device->session_id,
                'isLegacy' => false
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData && isset($responseData['data']) && isset($responseData['data']['qr'])) {
                    return response()->json([
                        'success' => true,
                        'message' => 'QR obtenido exitosamente',
                        'data' => [
                            'qr' => $responseData['data']['qr'],
                            'session_id' => $device->session_id,
                            'status' => $device->status,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al crear sesión o no se pudo generar el QR. Verifica que el servidor esté funcionando.',
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear sesión o no se pudo generar el QR. Verifica que el servidor esté funcionando.',
                ], 500);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el código QR. El servidor WhatsApp puede estar ocupado.',
            ], 500);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con el servidor WhatsApp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener código QR para conectar (VERSIÓN DE PRUEBA - SIN AUTENTICACIÓN)
     */
    public function getQrCodeTest(Device $device): JsonResponse
    {
        try {
            if (!$device->needsQr()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El dispositivo no requiere código QR',
                ], 400);
            }

            $whatsappServerUrl = config('app.whatsapp_server_url', 'http://localhost:3000');
            
            // Usar el nuevo endpoint que combina creación y QR (más rápido)
            $response = Http::timeout(5)->post($whatsappServerUrl . '/session/create-and-qr', [
                'sessionId' => $device->session_id,
                'isLegacy' => false
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                if ($responseData && isset($responseData['data']) && isset($responseData['data']['qr'])) {
                    return response()->json([
                        'success' => true,
                        'message' => 'QR obtenido exitosamente (TEST)',
                        'data' => [
                            'qr' => $responseData['data']['qr'],
                            'session_id' => $device->session_id,
                            'status' => $device->status,
                        ],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al crear sesión o no se pudo generar el QR. Verifica que el servidor esté funcionando.',
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear sesión o no se pudo generar el QR. Verifica que el servidor esté funcionando.',
                ], 500);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el código QR. El servidor WhatsApp puede estar ocupado.',
            ], 500);
            
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión con el servidor WhatsApp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conectar dispositivo WhatsApp
     */
    public function connectWhatsApp(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        // Verificar si ya está conectado o en proceso de conexión
        if ($device->status === 'connected') {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo ya está conectado a WhatsApp',
                'data' => $device
            ], 400);
        }

        if ($device->status === 'qr_required' || $device->status === 'connecting') {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo ya está en proceso de conexión',
                'data' => $device
            ], 400);
        }

        try {
            $whatsappServerUrl = config('app.whatsapp_server_url', 'http://localhost:3000');
            
            // Verificar si ya existe una sesión conectada
            $statusResponse = Http::timeout(3)->get($whatsappServerUrl . '/session/status/' . $device->session_id);
            
            if ($statusResponse->successful()) {
                $statusData = $statusResponse->json();
                if ($statusData && isset($statusData['data']) && isset($statusData['data']['isConnected']) && $statusData['data']['isConnected']) {
                    // La sesión ya existe y está conectada
                    $device->markAsConnected();
                    return response()->json([
                        'success' => true,
                        'message' => 'Dispositivo ya conectado a WhatsApp',
                        'data' => [
                            'device' => $device,
                        ]
                    ]);
                }
            }

            // Crear sesión en el servidor WhatsApp
            $response = Http::timeout(5)->post($whatsappServerUrl . '/session/create', [
                'sessionId' => $device->session_id,
                'isLegacy' => false
            ]);

            if ($response->successful()) {
                $device->update([
                    'status' => 'qr_required',
                    'error_message' => null
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Sesión WhatsApp creada. Escanea el código QR.',
                    'data' => [
                        'device' => $device,
                    ]
                ]);
            } else {
                $device->update([
                    'status' => 'error',
                    'error_message' => 'Error al conectar con el servidor WhatsApp'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al conectar con el servidor WhatsApp',
                    'data' => $device
                ], 500);
            }
        } catch (\Exception $e) {
            $device->update([
                'status' => 'error',
                'error_message' => 'Error de conexión: ' . $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar: ' . $e->getMessage(),
                'data' => $device
            ], 500);
        }
    }

    /**
     * Desconectar dispositivo WhatsApp
     */
    public function disconnectWhatsApp(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        try {
            $whatsappServerUrl = config('app.whatsapp_server_url', 'http://localhost:3000');
            
            // Eliminar sesión del servidor WhatsApp
            $response = Http::timeout(5)->delete($whatsappServerUrl . '/session/delete/' . $device->session_id);

            // Marcar como desconectado independientemente del resultado del servidor
            $device->markAsDisconnected('Desconectado por el usuario');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo desconectado y sesión eliminada exitosamente',
                    'data' => $device
                ]);
            } else {
                // Si el servidor no responde, pero marcamos como desconectado
                return response()->json([
                    'success' => true,
                    'message' => 'Dispositivo desconectado (servidor no disponible)',
                    'data' => $device
                ]);
            }
        } catch (\Exception $e) {
            // Aún así marcar como desconectado
            $device->markAsDisconnected('Desconectado por el usuario (error: ' . $e->getMessage() . ')');
            
            return response()->json([
                'success' => true,
                'message' => 'Dispositivo desconectado (error de servidor: ' . $e->getMessage() . ')',
                'data' => $device
            ]);
        }
    }

    /**
     * Obtener estado de conexión WhatsApp
     */
    public function getWhatsAppStatus(Device $device): JsonResponse
    {
        if ($device->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        try {
            $whatsappServerUrl = config('app.whatsapp_server_url', 'http://localhost:3000');
            
            $response = Http::timeout(5)->get($whatsappServerUrl . '/session/status/' . $device->session_id);

            if ($response->successful()) {
                $statusData = $response->json();
                
                // Verificar que la respuesta tiene la estructura esperada
                if ($statusData && isset($statusData['data']) && isset($statusData['data']['isConnected'])) {
                    // Actualizar estado del dispositivo según la respuesta
                    if ($statusData['data']['isConnected']) {
                        $device->update([
                            'status' => 'connected',
                            'connected_at' => now(),
                            'error_message' => null
                        ]);
                    } else {
                        $device->update(['status' => 'disconnected']);
                    }

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'device' => $device,
                            'whatsapp_status' => $statusData['data']
                        ]
                    ]);
                } else {
                    // Respuesta no tiene la estructura esperada
                    return response()->json([
                        'success' => false,
                        'message' => 'Respuesta del servidor WhatsApp inválida',
                        'data' => $device
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener estado de WhatsApp',
                    'data' => $device
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado: ' . $e->getMessage(),
                'data' => $device
            ], 500);
        }
    }

    /**
     * Conectar dispositivo (método legacy)
     */
    public function connect(Device $device): JsonResponse
    {
        return $this->connectWhatsApp($device);
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

        $device->markAsDisconnected('Desconectado por el usuario');

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo desconectado',
            'data' => $device,
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
            'contacts_count' => $device->contacts()->count(),
            'conversations_count' => $device->conversations()->count(),
            'messages_today' => $device->conversations()
                ->with('messages')
                ->get()
                ->sum(function ($conversation) {
                    return $conversation->messages()
                        ->whereDate('created_at', today())
                        ->count();
                }),
            'unread_conversations' => $device->conversations()
                ->withUnread()
                ->count(),
            'last_activity' => $device->last_seen,
            'uptime' => $device->connected_at ? 
                now()->diffInMinutes($device->connected_at) : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Buscar usuarios para filtros tipo search
     * Endpoint para buscador de usuarios (id/name)
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        
        if (strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Mínimo 2 caracteres para buscar'
            ]);
        }

        $users = \App\Models\User::where(function($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
        })
        ->select('id', 'name', 'email')
        ->limit(10)
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'description' => $user->email, // Para uniformidad con otros endpoints
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
