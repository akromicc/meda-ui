<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Closure; // Import Closure for type hinting
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class QuerySortingService
{
    /**
     * Sorts, filters, and searches an Eloquent model's query based on request parameters.
     * Returns a standardized JSON response with pagination data.
     *
     * @param Model $model The Eloquent model instance to query.
     * @param Request $request The incoming HTTP request containing sorting, filtering, and search parameters.
     * @param array $relations An array of relations to eager load with the query.
     * @param array $searchColumns An array of columns to search across (can include relation columns like 'relation:column').
     * @param Closure|null $customQuery An optional callback to apply additional constraints to the query.
     * @param int $perPage The number of items per page for pagination. Use -1 for no pagination.
     * @param string|null $defaultSortBy The default column to sort by if no 'sortBy' is provided in the request.
     * @param string|null $defaultOrder The default order (asc/desc) if no 'order' is provided in the request.
     * @param array $additionalData Additional data to include in the response.
     * @return JsonResponse Standardized JSON response with data, pagination, and filters.
     * @throws \InvalidArgumentException If the model's table does not exist.
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

            // Preparar respuesta para paginación
            if ($perPage !== -1) {
                $response = [
                    'success' => true,
                    'data' => $result->items(),
                    'pagination' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                        'from' => $result->firstItem(),
                        'to' => $result->lastItem(),
                        'has_more_pages' => $result->hasMorePages(),
                        'next_page_url' => $result->nextPageUrl(),
                        'prev_page_url' => $result->previousPageUrl(),
                    ],
                    'filters' => $this->getAppliedFilters($request),
                ];
            } else {
                // Sin paginación
                $response = [
                    'success' => true,
                    'data' => $result->toArray(),
                    'total' => $result->count(),
                    'filters' => $this->getAppliedFilters($request),
                ];
            }

            // Agregar datos adicionales si se proporcionan
            if (!empty($additionalData)) {
                $response = array_merge($response, $additionalData);
            }

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
     * Sorts, filters, and searches an Eloquent model's query based on request parameters.
     *
     * @param Model $model The Eloquent model instance to query.
     * @param Request $request The incoming HTTP request containing sorting, filtering, and search parameters.
     * @param array $relations An array of relations to eager load with the query.
     * @param array $searchColumns An array of columns to search across (can include relation columns like 'relation:column').
     * @param Closure|null $customQuery An optional callback to apply additional constraints to the query.
     * @param int $perPage The number of items per page for pagination. Use -1 for no pagination.
     * @param string|null $defaultSortBy The default column to sort by if no 'sortBy' is provided in the request.
     * @param string|null $defaultOrder The default order (asc/desc) if no 'order' is provided in the request.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException If the model's table does not exist.
     */
    public function sortTable(
        Model $model,
        Request $request,
        array $relations = [],
        array $searchColumns = [],
        ?Closure $customQuery = null,
        int $perPage = 10,
        ?string $defaultSortBy = 'created_at', // Made defaultSortBy flexible
        ?string $defaultOrder = 'desc'        // Made defaultOrder flexible
    ) {
        $table = $model->getTable(); // Dynamically get the table name from the model

        if (!Schema::hasTable($table)) {
            throw new \InvalidArgumentException("La tabla '{$table}' no existe.");
        }

        $query = $model::query();

        // Apply custom query if provided
        if ($customQuery instanceof Closure) {
            $customQuery($query);
        }

        // Apply filters (f_ prefix)
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'f_')) {
                $field = substr($key, 2);

                // Manejar filtros con relaciones (ej: f_user_id:user.name=john)
                if (strpos($field, ':') !== false) {
                    list($relationField, $relationColumn) = explode(':', $field, 2);
                    
                    // Extract relation name and column (ej: user.name -> relation: user, column: name)
                    if (strpos($relationColumn, '.') !== false) {
                        list($relation, $column) = explode('.', $relationColumn, 2);
                        
                        if (method_exists($model, $relation)) {
                            $query->whereHas($relation, function ($q) use ($column, $value) {
                                // Manejar rangos de fechas en relaciones
                                if (strpos($value, '_') !== false) {
                                    $parts = explode('_', $value);
                                    if (count($parts) === 2 && strtotime(trim($parts[0])) && strtotime(trim($parts[1]))) {
                                        $startDate = Carbon::parse(trim($parts[0]))->startOfDay();
                                        $endDate = Carbon::parse(trim($parts[1]))->endOfDay();
                                        $q->whereBetween($column, [$startDate, $endDate]);
                                    }
                                }
                                // Manejar rangos numéricos en relaciones
                                elseif (strpos($value, '-') !== false) {
                                    $parts = explode('-', $value);
                                    if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                                        $min = trim($parts[0]);
                                        $max = trim($parts[1]);
                                        $q->whereBetween($column, [$min, $max]);
                                    }
                                }
                                // Búsqueda tipo LIKE para texto en relaciones
                                else {
                                    $q->where($column, 'like', "%{$value}%");
                                }
                            });
                        }
                    }
                }
                // Manejar rangos de fechas (ej: f_created_at=2023-01-01_2023-01-31)
                elseif (strpos($value, '_') !== false) {
                    $parts = explode('_', $value);
                    if (count($parts) === 2 && strtotime(trim($parts[0])) && strtotime(trim($parts[1]))) {
                        $startDate = Carbon::parse(trim($parts[0]))->startOfDay();
                        $endDate = Carbon::parse(trim($parts[1]))->endOfDay();
                        $query->whereBetween("{$table}.{$field}", [$startDate, $endDate]);
                    }
                }
                // Manejar rangos numéricos (ej: f_quantity=10-50)
                elseif (strpos($value, '-') !== false) {
                    $parts = explode('-', $value);
                    if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                        $min = trim($parts[0]);
                        $max = trim($parts[1]);
                        $query->whereBetween("{$table}.{$field}", [$min, $max]);
                    }
                }
                // Manejar filtro de valor único (ej: f_status=activo)
                else {
                    // Para campos de texto usar LIKE, para otros campos usar igualdad exacta
                    if (Schema::hasColumn($table, $field)) {
                        $columnType = Schema::getColumnType($table, $field);
                        if (in_array($columnType, ['string', 'text'])) {
                            $query->where("{$table}.{$field}", 'like', "%{$value}%");
                        } else {
                            $query->where("{$table}.{$field}", $value);
                        }
                    } else {
                        $query->where("{$table}.{$field}", $value);
                    }
                }
            }
        }

        $sortBy = $request->get('sortBy');
        $order = $request->get('order');
        $search = $request->get('search');

        // Apply default sorting if not present in request
        if (!$sortBy && $defaultSortBy) {
            $sortBy = $defaultSortBy;
            $order = $defaultOrder ?? 'desc'; // Use defaultOrder if provided, else 'desc'
        }

        // Apply sorting
        if ($sortBy) {
            $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

            // Handle sorting by relation column (e.g., sortBy=r:user.name)
            if (str_starts_with($sortBy, 'r:')) {
                list($relation, $column) = explode('.', substr($sortBy, 2));

                if (method_exists($model, $relation)) {
                    $relatedModelInstance = $model->$relation()->getRelated();
                    $relatedTable = $relatedModelInstance->getTable();
                    $foreignKey = $model->$relation()->getForeignKeyName();
                    $ownerKey = $model->$relation()->getOwnerKeyName();

                    $query->join($relatedTable, "{$relatedTable}.{$ownerKey}", '=', "{$table}.{$foreignKey}")
                          ->orderBy("{$relatedTable}.{$column}", $order);
                }
            }
            // Handle sorting by direct column
            elseif (Schema::hasColumn($table, $sortBy)) {
                $query->orderBy("{$table}.{$sortBy}", $order); // Ensure table prefix for clarity
            }
            // Handle sorting by calculated fields (e.g., clients_count from withCount)
            elseif (str_ends_with($sortBy, '_count')) {
                $query->orderBy($sortBy, $order);
            }
        }

        // Eager load relations
        if (!empty($relations)) {
            $query->with($relations);
        }

        // Apply search
        if ($search && !empty($searchColumns)) {
            $query->where(function ($subQuery) use ($table, $searchColumns, $search, $model) {
                // Dividir el término de búsqueda en palabras
                $searchTerms = preg_split('/\s+/', trim($search));
                
                foreach ($searchColumns as $column) {
                    // Handle searching by relation column (e.g., searchColumns=['r:user.name'])
                    if (str_starts_with($column, 'r:')) {
                        list($relation, $relatedColumn) = explode('.', substr($column, 2));

                        if (method_exists($model, $relation)) {
                            $relatedModelInstance = $model->$relation()->getRelated();
                            $relatedTable = $relatedModelInstance->getTable();
                            $foreignKey = $model->$relation()->getForeignKeyName();
                            $ownerKey = $model->$relation()->getOwnerKeyName();

                            $subQuery->orWhereHas($relation, function ($q) use ($relatedColumn, $search, $searchTerms) {
                                // Búsqueda exacta
                                $q->where($relatedColumn, 'like', "%{$search}%");
                                
                                // Búsqueda por palabras individuales si hay múltiples términos
                                if (count($searchTerms) > 1) {
                                    foreach ($searchTerms as $term) {
                                        if (strlen($term) >= 2) {
                                            $q->orWhere($relatedColumn, 'like', "%{$term}%");
                                        }
                                    }
                                }
                            });
                        }
                    }
                    // Handle searching by direct column
                    elseif (Schema::hasColumn($table, $column)) {
                        // Búsqueda exacta
                        $subQuery->orWhere("{$table}.{$column}", 'like', "%{$search}%");
                        
                        // Búsqueda por palabras individuales si hay múltiples términos
                        if (count($searchTerms) > 1) {
                            foreach ($searchTerms as $term) {
                                if (strlen($term) >= 2) {
                                    $subQuery->orWhere("{$table}.{$column}", 'like', "%{$term}%");
                                }
                            }
                        }
                    }
                }
            });
        }

        // Paginate or get all results
        if ($perPage === -1) {
            return $query->get();
        }

        $paginatedResults = $query->paginate($perPage);

        // Append current query parameters to pagination links
        $currentQuery = $request->query();
        $paginatedResults->appends($currentQuery); // Appends all current query parameters

        return $paginatedResults;
    }

    /**
     * Get applied filters from request
     */
    private function getAppliedFilters(Request $request): array
    {
        $filters = [];
        
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'f_') && !empty($value)) {
                $field = substr($key, 2);
                $filters[$field] = $value;
            }
        }

        // Add common filters
        if ($request->has('search')) {
            $filters['search'] = $request->get('search');
        }

        if ($request->has('sortBy')) {
            $filters['sortBy'] = $request->get('sortBy');
        }

        if ($request->has('order')) {
            $filters['order'] = $request->get('order');
        }

        return $filters;
    }

    /**
     * Apply query sorting to an existing query builder
     */
    public function apply(Request $request, $query, array $config = [])
    {
        $table = $query->getModel()->getTable();
        
        // Apply filters (f_ prefix)
        foreach ($request->query() as $key => $value) {
            if (str_starts_with($key, 'f_')) {
                $field = substr($key, 2);
                $model = $query->getModel();

                // Manejar filtros con relaciones (ej: f_user_id:user.name=john)
                if (strpos($field, ':') !== false) {
                    list($relationField, $relationColumn) = explode(':', $field, 2);
                    
                    // Extract relation name and column (ej: user.name -> relation: user, column: name)
                    if (strpos($relationColumn, '.') !== false) {
                        list($relation, $column) = explode('.', $relationColumn, 2);
                        
                        if (method_exists($model, $relation)) {
                            $query->whereHas($relation, function ($q) use ($column, $value) {
                                // Manejar rangos de fechas en relaciones
                                if (strpos($value, '_') !== false) {
                                    $parts = explode('_', $value);
                                    if (count($parts) === 2 && strtotime(trim($parts[0])) && strtotime(trim($parts[1]))) {
                                        $startDate = Carbon::parse(trim($parts[0]))->startOfDay();
                                        $endDate = Carbon::parse(trim($parts[1]))->endOfDay();
                                        $q->whereBetween($column, [$startDate, $endDate]);
                                    }
                                }
                                // Manejar rangos numéricos en relaciones
                                elseif (strpos($value, '-') !== false) {
                                    $parts = explode('-', $value);
                                    if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                                        $min = trim($parts[0]);
                                        $max = trim($parts[1]);
                                        $q->whereBetween($column, [$min, $max]);
                                    }
                                }
                                // Búsqueda tipo LIKE para texto en relaciones
                                else {
                                    $q->where($column, 'like', "%{$value}%");
                                }
                            });
                        }
                    }
                }
                // Handle date ranges
                elseif (strpos($value, '_') !== false) {
                    $parts = explode('_', $value);
                    if (count($parts) === 2 && strtotime(trim($parts[0])) && strtotime(trim($parts[1]))) {
                        $startDate = Carbon::parse(trim($parts[0]))->startOfDay();
                        $endDate = Carbon::parse(trim($parts[1]))->endOfDay();
                        $query->whereBetween("{$table}.{$field}", [$startDate, $endDate]);
                    }
                }
                // Handle numeric ranges
                elseif (strpos($value, '-') !== false) {
                    $parts = explode('-', $value);
                    if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                        $min = trim($parts[0]);
                        $max = trim($parts[1]);
                        $query->whereBetween("{$table}.{$field}", [$min, $max]);
                    }
                }
                // Handle single value filter
                else {
                    // Para campos de texto usar LIKE, para otros campos usar igualdad exacta
                    if (Schema::hasColumn($table, $field)) {
                        $columnType = Schema::getColumnType($table, $field);
                        if (in_array($columnType, ['string', 'text'])) {
                            $query->where("{$table}.{$field}", 'like', "%{$value}%");
                        } else {
                            $query->where("{$table}.{$field}", $value);
                        }
                    } else {
                        $query->where("{$table}.{$field}", $value);
                    }
                }
            }
        }

        // Apply sorting
        $sortBy = $request->get('sortBy', $config['defaultSortBy'] ?? 'created_at');
        $order = $request->get('order', $config['defaultOrder'] ?? 'desc');

        if ($sortBy) {
            $order = strtolower($order) === 'desc' ? 'desc' : 'asc';

            // Handle sorting by relation column
            if (str_starts_with($sortBy, 'r:')) {
                list($relation, $column) = explode('.', substr($sortBy, 2));
                $model = $query->getModel();

                if (method_exists($model, $relation)) {
                    $relatedModelInstance = $model->$relation()->getRelated();
                    $relatedTable = $relatedModelInstance->getTable();
                    $foreignKey = $model->$relation()->getForeignKeyName();
                    $ownerKey = $model->$relation()->getOwnerKeyName();

                    $query->join($relatedTable, "{$relatedTable}.{$ownerKey}", '=', "{$table}.{$foreignKey}")
                          ->orderBy("{$relatedTable}.{$column}", $order);
                }
            }
            // Handle sorting by direct column
            elseif (Schema::hasColumn($table, $sortBy)) {
                $query->orderBy("{$table}.{$sortBy}", $order);
            }
        }

        // Apply search
        $search = $request->get('search');
        $searchColumns = $config['searchColumns'] ?? [];

        if ($search && !empty($searchColumns)) {
            $query->where(function ($subQuery) use ($table, $searchColumns, $search, $query) {
                $searchTerms = preg_split('/\s+/', trim($search));
                $model = $query->getModel();
                
                foreach ($searchColumns as $column) {
                    if (str_starts_with($column, 'r:')) {
                        list($relation, $relatedColumn) = explode('.', substr($column, 2));

                        if (method_exists($model, $relation)) {
                            $subQuery->orWhereHas($relation, function ($q) use ($relatedColumn, $search, $searchTerms) {
                                $q->where($relatedColumn, 'like', "%{$search}%");
                                
                                if (count($searchTerms) > 1) {
                                    foreach ($searchTerms as $term) {
                                        if (strlen($term) >= 2) {
                                            $q->orWhere($relatedColumn, 'like', "%{$term}%");
                                        }
                                    }
                                }
                            });
                        }
                    }
                    elseif (Schema::hasColumn($table, $column)) {
                        $subQuery->orWhere("{$table}.{$column}", 'like', "%{$search}%");
                        
                        if (count($searchTerms) > 1) {
                            foreach ($searchTerms as $term) {
                                if (strlen($term) >= 2) {
                                    $subQuery->orWhere("{$table}.{$column}", 'like', "%{$term}%");
                                }
                            }
                        }
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Get paginated response from a query
     */
    public function getPaginatedResponse(Request $request, $query, array $config = [])
    {
        $perPage = $request->get('per_page', $config['perPage'] ?? 15);
        $relations = $config['relations'] ?? [];

        // Eager load relations
        if (!empty($relations)) {
            $query->with($relations);
        }

        $result = $query->paginate($perPage);

        // Append current query parameters to pagination links
        $currentQuery = $request->query();
        $result->appends($currentQuery);

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
                'has_more_pages' => $result->hasMorePages(),
                'next_page_url' => $result->nextPageUrl(),
                'prev_page_url' => $result->previousPageUrl(),
            ],
            'filters' => $this->getAppliedFilters($request),
        ]);
    }
}
