<?php

namespace Meda\DynamicUI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Meda\DynamicUI\Services\DynamicUIService;
use Meda\DynamicUI\Services\AutoRouteService;

/**
 * Controlador Automático Universal
 * 
 * Maneja automáticamente todas las operaciones CRUD para cualquier modelo
 * basándose en la ruta dinámica proporcionada.
 * 
 * Uso: Route::any('/dynamic/{model}/{action?}/{id?}', [AutoDynamicController::class, 'handle']);
 */
class AutoDynamicController extends Controller
{
    protected $dynamicUIService;
    protected $autoRouteService;

    public function __construct(DynamicUIService $dynamicUIService, AutoRouteService $autoRouteService)
    {
        $this->dynamicUIService = $dynamicUIService;
        $this->autoRouteService = $autoRouteService;
    }

    /**
     * Manejar todas las operaciones dinámicamente
     * 
     * Rutas soportadas:
     * GET    /dynamic/{model}           -> index
     * POST   /dynamic/{model}           -> store
     * GET    /dynamic/{model}/{id}      -> show
     * PUT    /dynamic/{model}/{id}      -> update
     * DELETE /dynamic/{model}/{id}      -> destroy
     * GET    /dynamic/{model}/metadata  -> metadata
     * POST   /dynamic/{model}/{id}/{action} -> acción personalizada
     */
    public function handle(Request $request, string $model, string $actionOrId = null, string $id = null): JsonResponse
    {
        try {
            // Resolver el modelo y la acción
            $resolvedModel = $this->autoRouteService->resolveModel($model);
            $operation = $this->autoRouteService->resolveOperation($request, $actionOrId, $id);
            
            // Verificar que el modelo existe
            if (!$resolvedModel) {
                return response()->json([
                    'success' => false,
                    'message' => "Modelo '{$model}' no encontrado o no configurado para uso dinámico"
                ], 404);
            }

            // Ejecutar la operación
            return $this->executeOperation($request, $resolvedModel, $operation);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en operación dinámica: ' . $e->getMessage(),
                'error' => app()->hasDebugModeEnabled() ? $e->getTrace() : null
            ], 500);
        }
    }

    /**
     * Ejecutar la operación determinada
     */
    protected function executeOperation(Request $request, string $modelClass, array $operation): JsonResponse
    {
        $action = $operation['action'];
        $params = $operation['params'];

        switch ($action) {
            case 'index':
                return $this->handleIndex($request, $modelClass);
                
            case 'store':
                return $this->handleStore($request, $modelClass);
                
            case 'show':
                return $this->handleShow($request, $modelClass, $params['id']);
                
            case 'update':
                return $this->handleUpdate($request, $modelClass, $params['id']);
                
            case 'destroy':
                return $this->handleDestroy($request, $modelClass, $params['id']);
                
            case 'metadata':
                return $this->handleMetadata($request, $modelClass);
                
            default:
                // Acción personalizada
                return $this->handleCustomAction($request, $modelClass, $action, $params);
        }
    }

    /**
     * Manejar listado (index)
     */
    protected function handleIndex(Request $request, string $modelClass): JsonResponse
    {
        $config = $this->autoRouteService->getModelConfig($modelClass);
        
        return $this->dynamicUIService->processDynamicView(
            $modelClass,
            $request,
            $config
        );
    }

    /**
     * Manejar creación (store)
     */
    protected function handleStore(Request $request, string $modelClass): JsonResponse
    {
        $model = new $modelClass();
        
        // Validación automática basada en metadatos
        $this->autoRouteService->validateRequest($request, $modelClass, 'create');
        
        // Crear el registro
        $data = $request->only($model->getFillable());
        $created = $modelClass::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro creado exitosamente',
            'data' => $created
        ], 201);
    }

    /**
     * Manejar mostrar (show)
     */
    protected function handleShow(Request $request, string $modelClass, string $id): JsonResponse
    {
        $config = $this->autoRouteService->getModelConfig($modelClass);
        $relations = $config['relations'] ?? [];
        
        $item = $modelClass::with($relations)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Manejar actualización (update)
     */
    protected function handleUpdate(Request $request, string $modelClass, string $id): JsonResponse
    {
        $item = $modelClass::findOrFail($id);
        
        // Validación automática basada en metadatos
        $this->autoRouteService->validateRequest($request, $modelClass, 'update', $item);
        
        // Actualizar el registro
        $data = $request->only($item->getFillable());
        $item->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado exitosamente',
            'data' => $item
        ]);
    }

    /**
     * Manejar eliminación (destroy)
     */
    protected function handleDestroy(Request $request, string $modelClass, string $id): JsonResponse
    {
        $item = $modelClass::findOrFail($id);
        
        // Verificar si se puede eliminar
        if (method_exists($item, 'canDelete') && !$item->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Este registro no se puede eliminar'
            ], 403);
        }
        
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado exitosamente'
        ]);
    }

    /**
     * Manejar metadatos
     */
    protected function handleMetadata(Request $request, string $modelClass): JsonResponse
    {
        $metadata = $this->dynamicUIService->getModelMetadata($modelClass);
        
        return response()->json([
            'success' => true,
            'data' => $metadata
        ]);
    }

    /**
     * Manejar acciones personalizadas
     */
    protected function handleCustomAction(Request $request, string $modelClass, string $action, array $params): JsonResponse
    {
        $id = $params['id'] ?? null;
        $item = $id ? $modelClass::findOrFail($id) : null;
        
        // Buscar el método de acción personalizada en el modelo
        $methodName = 'handle' . ucfirst($action);
        
        if ($item && method_exists($item, $methodName)) {
            $result = $item->$methodName($request);
            
            if ($result instanceof JsonResponse) {
                return $result;
            }
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Acción '{$action}' ejecutada exitosamente"
            ]);
        }
        
        // Buscar en el servicio de rutas automáticas
        return $this->autoRouteService->handleCustomAction($request, $modelClass, $action, $item);
    }
}