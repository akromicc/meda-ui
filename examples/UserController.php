<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Meda\DynamicUI\Http\Controllers\DynamicController;
use Meda\DynamicUI\Services\DynamicUIService;

/**
 * Controlador de ejemplo usando Dynamic UI
 * 
 * Este ejemplo muestra cómo crear un controlador CRUD
 * completo usando Dynamic UI con el mínimo código posible.
 * Las acciones se generan automáticamente de los modales definidos.
 */
class UserController extends Controller
{
    protected $dynamicController;

    public function __construct(DynamicUIService $dynamicUIService)
    {
        // Configurar el controlador dinámico con el modelo User
        $this->dynamicController = (new DynamicController($dynamicUIService))
            ->configure(User::class, [
                'relations' => [], // Sin relaciones en este ejemplo
                'searchColumns' => ['name', 'email', 'phone'],
                'perPage' => 15,
                'defaultSortBy' => 'created_at',
                'defaultOrder' => 'desc'
            ]);
    }

    /**
     * Listar usuarios con filtros, búsqueda y paginación
     * 
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        return $this->dynamicController->index($request);
    }

    /**
     * Crear nuevo usuario
     * 
     * POST /api/users
     */
    public function store(Request $request): JsonResponse
    {
        return $this->dynamicController->store($request);
    }

    /**
     * Mostrar usuario específico
     * 
     * GET /api/users/{id}
     */
    public function show(string $id): JsonResponse
    {
        return $this->dynamicController->show($id);
    }

    /**
     * Actualizar usuario
     * 
     * PUT /api/users/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return $this->dynamicController->update($request, $id);
    }

    /**
     * Eliminar usuario
     * 
     * DELETE /api/users/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->dynamicController->destroy($id);
    }

    /**
     * Obtener metadatos del modelo (tabla, modal, acciones)
     * 
     * GET /api/users/metadata
     */
    public function metadata(): JsonResponse
    {
        return $this->dynamicController->metadata();
    }

    /**
     * Acción personalizada: Activar/Desactivar usuario
     * Esta acción se genera automáticamente del modal 'toggle_status'
     * definido en el modelo User
     * 
     * POST /api/users/{id}/toggle-status
     */
    public function toggleStatus(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Verificar permisos (no permitir desactivar admins)
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el estado de un administrador'
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active 
                ? 'Usuario activado exitosamente' 
                : 'Usuario desactivado exitosamente',
            'data' => $user
        ]);
    }

    /**
     * Acción personalizada: Duplicar usuario
     * Esta acción se genera automáticamente del modal 'duplicate'
     * definido en el modelo User
     * 
     * POST /api/users/{id}/duplicate
     */
    public function duplicate(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Crear copia del usuario
        $newUser = $user->replicate();
        $newUser->name = $user->name . ' (Copia)';
        $newUser->email = 'copia_' . time() . '_' . $user->email;
        $newUser->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario duplicado exitosamente',
            'data' => $newUser
        ]);
    }

    /**
     * Acción personalizada: Exportar usuarios
     * Esta acción se genera automáticamente del modal 'export'
     * definido en el modelo User
     * 
     * GET /api/users/export
     */
    public function export(Request $request): JsonResponse
    {
        $users = User::all();
        
        // Simular exportación
        $exportData = $users->map(function($user) {
            return [
                'ID' => $user->id,
                'Nombre' => $user->name,
                'Email' => $user->email,
                'Teléfono' => $user->phone,
                'Estado' => $user->is_active ? 'Activo' : 'Inactivo',
                'Creado' => $user->created_at->format('d/m/Y H:i')
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Exportación completada',
            'data' => $exportData,
            'filename' => 'usuarios_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
    }

    /**
     * Búsqueda de usuarios para campos de tipo 'search'
     * 
     * GET /api/search/users
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        
        if (empty($search) || strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $users = User::where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'description' => $user->email
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}