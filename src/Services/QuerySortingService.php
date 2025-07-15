<?php

namespace Meda\DynamicUI\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Closure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class QuerySortingService
{
    /**
     * Obtiene respuesta JSON estandarizada con paginación, filtros y búsqueda
     */
    public function getJsonResponse(
        Model $model,
        Request $request,
        array $relations = [],
        array $searchColumns = [],
        ?Closure $customQuery = null,
        int $perPage = 15,
        ?string $defaultSortBy = 'created_at',
        ?string $defaultOrder = 'desc',
        array $additionalData = []
    ): JsonResponse {
        try {
            $result = $this->sortTable(
                $model,
                $request,
                $relations,
                $searchColumns,
                $customQuery,
                $perPage,
                $defaultSortBy,
                $defaultOrder
            );

            $response = $this->buildResponse($result, $request, $perPage, $additionalData);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('QuerySortingService Error: ' . $e->getMessage(), [
                'model' => get_class($model),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la consulta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Ordena, filtra y busca en un modelo Eloquent
     */
    public function sortTable(
        Model $model,
        Request $request,
        array $relations = [],
        array $searchColumns = [],
        ?Closure $customQuery = null,
        int $perPage = 10,
        ?string $defaultSortBy = 'created_at',
        ?string $defaultOrder = 'desc'
    ) {
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            throw new \InvalidArgumentException("La tabla '{$table}' no existe.");
        }

        $query = $model::query();

        // Aplicar query personalizada
        if ($customQuery instanceof Closure) {
            $customQuery($query);
        }

        // Aplicar filtros
        $this->applyFilters($query, $request, $table);

        // Aplicar búsqueda
        $this->applySearch($query, $request, $searchColumns, $table);

        // Aplicar ordenamiento
        $this->applySorting($query, $request, $defaultSortBy, $defaultOrder, $table);

        // Cargar relaciones
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Aplicar paginación
        if ($perPage !== -1) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Aplicar filtros a la consulta
     */
    protected function applyFilters($query, Request $request, string $table): void
    {
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'f_')) {
                $field = substr($key, 2);
                $this->applyFilter($query, $field, $value, $table);
            }
        }
    }

    /**
     * Aplicar un filtro específico
     */
    protected function applyFilter($query, string $field, $value, string $table): void
    {
        // Filtros con relaciones (ej: f_user_id:user.name=john)
        if (strpos($field, ':') !== false) {
            $this->applyRelationFilter($query, $field, $value);
            return;
        }

        // Rangos de fechas (ej: f_created_at=2023-01-01_2023-01-31)
        if (strpos($value, '_') !== false && $this->isDateRange($value)) {
            $this->applyDateRangeFilter($query, $field, $value, $table);
            return;
        }

        // Rangos numéricos (ej: f_quantity=10-50)
        if (strpos($value, '-') !== false && $this->isNumericRange($value)) {
            $this->applyNumericRangeFilter($query, $field, $value, $table);
            return;
        }

        // Filtro de valor único
        $query->where("{$table}.{$field}", 'like', "%{$value}%");
    }

    /**
     * Aplicar filtro de relación
     */
    protected function applyRelationFilter($query, string $field, $value): void
    {
        list($relationField, $relationColumn) = explode(':', $field, 2);
        
        if (strpos($relationColumn, '.') !== false) {
            list($relation, $column) = explode('.', $relationColumn, 2);
            
            $query->whereHas($relation, function ($q) use ($column, $value) {
                if (strpos($value, '_') !== false && $this->isDateRange($value)) {
                    $this->applyDateRangeFilter($q, $column, $value);
                } elseif (strpos($value, '-') !== false && $this->isNumericRange($value)) {
                    $this->applyNumericRangeFilter($q, $column, $value);
                } else {
                    $q->where($column, 'like', "%{$value}%");
                }
            });
        }
    }

    /**
     * Aplicar filtro de rango de fechas
     */
    protected function applyDateRangeFilter($query, string $field, string $value, ?string $table = null): void
    {
        $parts = explode('_', $value);
        $startDate = Carbon::parse(trim($parts[0]))->startOfDay();
        $endDate = Carbon::parse(trim($parts[1]))->endOfDay();
        
        $fieldName = $table ? "{$table}.{$field}" : $field;
        $query->whereBetween($fieldName, [$startDate, $endDate]);
    }

    /**
     * Aplicar filtro de rango numérico
     */
    protected function applyNumericRangeFilter($query, string $field, string $value, ?string $table = null): void
    {
        $parts = explode('-', $value);
        $min = trim($parts[0]);
        $max = trim($parts[1]);
        
        $fieldName = $table ? "{$table}.{$field}" : $field;
        $query->whereBetween($fieldName, [$min, $max]);
    }

    /**
     * Aplicar búsqueda
     */
    protected function applySearch($query, Request $request, array $searchColumns, string $table): void
    {
        $searchTerm = $request->get('search');
        
        if (!$searchTerm || empty($searchColumns)) {
            return;
        }

        $query->where(function ($q) use ($searchTerm, $searchColumns, $table) {
            foreach ($searchColumns as $column) {
                if (strpos($column, 'r:') === 0) {
                    // Búsqueda en relación
                    $relationColumn = substr($column, 2);
                    if (strpos($relationColumn, '.') !== false) {
                        list($relation, $field) = explode('.', $relationColumn, 2);
                        $q->orWhereHas($relation, function ($subQuery) use ($field, $searchTerm) {
                            $subQuery->where($field, 'like', "%{$searchTerm}%");
                        });
                    }
                } else {
                    // Búsqueda en columna directa
                    $q->orWhere("{$table}.{$column}", 'like', "%{$searchTerm}%");
                }
            }
        });
    }

    /**
     * Aplicar ordenamiento
     */
    protected function applySorting($query, Request $request, ?string $defaultSortBy, ?string $defaultOrder, string $table): void
    {
        $sortBy = $request->get('sortBy', $defaultSortBy);
        $order = $request->get('order', $defaultOrder);

        if ($sortBy) {
            $query->orderBy("{$table}.{$sortBy}", $order);
        }
    }

    /**
     * Construir respuesta JSON
     */
    protected function buildResponse($result, Request $request, int $perPage, array $additionalData): array
    {
        $response = ['success' => true];

        if ($perPage !== -1) {
            $response['data'] = $result->items();
            $response['pagination'] = [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
                'has_more_pages' => $result->hasMorePages(),
                'next_page_url' => $result->nextPageUrl(),
                'prev_page_url' => $result->previousPageUrl(),
            ];
        } else {
            $response['data'] = $result->toArray();
            $response['total'] = $result->count();
        }

        $response['filters'] = $this->getAppliedFilters($request);

        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        return $response;
    }

    /**
     * Obtener filtros aplicados
     */
    protected function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'f_')) {
                $filters[substr($key, 2)] = $value;
            }
        }

        return $filters;
    }

    /**
     * Verificar si es un rango de fechas
     */
    protected function isDateRange(string $value): bool
    {
        $parts = explode('_', $value);
        return count($parts) === 2 && strtotime(trim($parts[0])) && strtotime(trim($parts[1]));
    }

    /**
     * Verificar si es un rango numérico
     */
    protected function isNumericRange(string $value): bool
    {
        $parts = explode('-', $value);
        return count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]));
    }
}