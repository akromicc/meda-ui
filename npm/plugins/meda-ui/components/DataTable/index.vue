<template>
  <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700" style="overflow: visible;">
    <!-- Skeleton loading completo: solo en primera carga (cuando no hay metadatos) -->
    <DataTableSkeleton v-if="isInitialLoading" />

    <!-- Toolbar, tabla y paginación reales (disponibles después de primera carga) -->
    <template v-else>
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <!-- Search -->
          <div class="flex-1 max-w-md">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </div>
              <input
                v-model="searchTerm"
                type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                :placeholder="dynamicSearchPlaceholder"
                @input="handleSearch"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <!-- Clear All Filters Button -->
            <button
              v-if="activeFiltersCount > 0"
              @click="clearAllFilters"
              class="inline-flex items-center px-3 py-2 border border-red-300 dark:border-red-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-red-500"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              Limpiar filtros ({{ activeFiltersCount }})
            </button>

            <!-- Per Page -->
            <select
              v-model="perPage"
              @change="changePerPage"
              class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option }} por página
              </option>
            </select>

            <!-- Refresh -->
            <button
              @click="refreshData"
              :disabled="isDataLoading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4" :class="{ 'animate-spin': isDataLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto" style="overflow-y: visible;">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th
                v-for="column in visibleColumns"
                :key="`header-${column.key}`"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider relative"
                style="overflow: visible;"
              >
                <div class="flex items-center justify-between">
                  <span 
                    class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-100"
                    @click="toggleSort(column.key)"
                  >
                    {{ column.label }}
                  </span>
                  <div class="flex items-center space-x-1">
                    <!-- Filter Icon -->
                    <ColumnFilter
                      v-if="column.filterable"
                      :column="column"
                      v-model="columnFilters[column.key]"
                      @apply="handleColumnFilter"
                      @clear="handleColumnFilterClear"
                      :align="visibleColumns.length - 1 === visibleColumns.indexOf(column) ? 'right' : 'left'"
                    />
                    
                    <!-- Sort Icons -->
                    <div v-if="column.sortable" class="flex flex-col cursor-pointer" @click="toggleSort(column.key)">
                      <svg
                        class="w-3 h-3"
                        :class="{
                          'text-primary-500': currentSort === column.key && sortOrder === 'asc',
                          'text-gray-400': !(currentSort === column.key && sortOrder === 'asc')
                        }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                      </svg>
                      <svg
                        class="w-3 h-3 -mt-1"
                        :class="{
                          'text-primary-500': currentSort === column.key && sortOrder === 'desc',
                          'text-gray-400': !(currentSort === column.key && sortOrder === 'desc')
                        }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>
              </th>
              <th v-if="dynamicActions && dynamicActions.length > 0" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Acciones
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Eliminado: Skeleton de filas durante recarga de datos -->
            
            <!-- No Data State -->
            <tr v-if="!isDataLoading && data.length === 0">
              <td :colspan="visibleColumns.length + (dynamicActions && dynamicActions.length > 0 ? 1 : 0)" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                No se encontraron resultados
              </td>
            </tr>

            <!-- Data Rows -->
            <tr v-else v-for="(item, index) in data" :key="item.id || index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td v-for="column in visibleColumns" :key="`cell-${item.id}-${column.key}`" class="px-6 py-4 whitespace-nowrap">
                <slot :name="`cell-${column.key}`" :item="item" :value="getNestedValue(item, column.key)">
                  <span class="text-sm text-gray-900 dark:text-gray-100">
                    {{ formatCellValue(getNestedValue(item, column.key), column) }}
                  </span>
                </slot>
              </td>
              <td v-if="dynamicActions && dynamicActions.length > 0" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end">
                  <DropdownMenu
                    :options="formatActionsForDropdown(dynamicActions, item)"
                    align="right"
                    min-width="32"
                    button-label="Acciones"
                    @option-click="handleActionClick"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.total > 0" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
        <div class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Anterior
            </button>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Mostrando
                <span class="font-medium">{{ pagination.from }}</span>
                a
                <span class="font-medium">{{ pagination.to }}</span>
                de
                <span class="font-medium">{{ pagination.total }}</span>
                resultados
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous Page -->
                <button
                  @click="goToPage(pagination.current_page - 1)"
                  :disabled="pagination.current_page <= 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>

                <!-- Page Numbers -->
                <template v-for="page in visiblePages" :key="page">
                  <button
                    v-if="page === '...'"
                    disabled
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300"
                  >
                    ...
                  </button>
                  <button
                    v-else
                    @click="goToPage(page)"
                    :class="{
                      'z-10 bg-primary-50 dark:bg-primary-900 border-primary-500 text-primary-600 dark:text-primary-300': page === pagination.current_page,
                      'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600': page !== pagination.current_page
                    }"
                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                  >
                    {{ page }}
                  </button>
                </template>

                <!-- Next Page -->
                <button
                  @click="goToPage(pagination.current_page + 1)"
                  :disabled="pagination.current_page >= pagination.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import ColumnFilter from './ColumnFilter.vue'
import DataTableSkeleton from './DataTableSkeleton.vue'
import DataTableRowsSkeleton from './DataTableRowsSkeleton.vue'
import DropdownMenu from '../DropdownMenu.vue'
import { useMetadata } from '../../composables/useMetadata.js'

export default {
  name: 'DataTable',
  components: {
    ColumnFilter,
    DataTableSkeleton,
    DataTableRowsSkeleton,
    DropdownMenu
  },
  props: {
    // API endpoint
    endpoint: {
      type: String,
      required: true
    },
    // Model name for automatic metadata loading
    modelName: {
      type: String,
      default: null
    },
    // Column definitions
    columns: {
      type: Array,
      default: () => []
    },
    // Actions for each row
    actions: {
      type: Array,
      default: () => []
    },
    // Search placeholder
    searchPlaceholder: {
      type: String,
      default: 'Buscar...'
    },
    // Per page options
    perPageOptions: {
      type: Array,
      default: () => [10, 15, 25, 50, 100]
    },
    // Default per page
    defaultPerPage: {
      type: Number,
      default: 15
    },
    // Auto-load on mount
    autoLoad: {
      type: Boolean,
      default: true
    },
    // Additional query parameters
    queryParams: {
      type: Object,
      default: () => ({})
    },
    // Permite controlar loading desde el padre
    loading: {
      type: Boolean,
      default: undefined
    }
  },
  emits: ['action', 'dataLoaded', 'error', 'refresh'],
  setup(props, { emit }) {
    // Importar useMetadata si se necesita
    const { getTableMetadata } = useMetadata()
    
    // Reactive data
    const data = ref([])
    const pagination = ref(null)
    const internalLoading = ref(false)
    const dataLoading = ref(false) // Nuevo estado para cargas parciales
    const hasInitialLoad = ref(false) // Bandera para saber si ya se hizo la primera carga
    const searchTerm = ref('')
    const currentSort = ref('')
    const sortOrder = ref('')
    const perPage = ref(props.defaultPerPage)
    
    // Metadatos dinámicos
    const tableMetadata = ref(null)
    const metadataLoading = ref(false)
    
    // Column-specific filters
    const columnFilters = reactive({})

    // Computed properties para metadatos dinámicos
    const dynamicColumns = computed(() => {
      if (props.modelName && tableMetadata.value?.columns) {
        return tableMetadata.value.columns
      }
      return props.columns
    })
    
    const dynamicActions = computed(() => {
      if (props.modelName && tableMetadata.value?.actions) {
        return tableMetadata.value.actions
      }
      return props.actions
    })
    
    const dynamicSearchPlaceholder = computed(() => {
      if (props.modelName && tableMetadata.value?.searchPlaceholder) {
        return tableMetadata.value.searchPlaceholder
      }
      return props.searchPlaceholder
    })

    // Cargar metadatos si se proporciona modelName
    const loadTableMetadata = async () => {
      if (!props.modelName) return
      
      metadataLoading.value = true
      try {
        const metadata = await getTableMetadata(props.modelName)
        if (metadata) {
          // Asignar opciones a cada columna según su key y tipo
          if (metadata.options && metadata.columns) {
            metadata.columns = metadata.columns.map(col => {
              if ((col.type === 'select' || col.type === 'boolean') && metadata.options[col.key]) {
                col.options = metadata.options[col.key]
              }
              return col
            })
          }
          tableMetadata.value = metadata
        }
      } catch (error) {
        console.error(`Error loading table metadata for ${props.modelName}:`, error)
      } finally {
        metadataLoading.value = false
      }
    }

    // Initialize column filters
    const initializeColumnFilters = () => {
      const columnsToUse = dynamicColumns.value
      columnsToUse.forEach(column => {
        if (column.filterable) {
          columnFilters[column.key] = null
        }
      })
    }

    // Computed properties
    const visibleColumns = computed(() => dynamicColumns.value.filter(col => !col.hidden))
    const activeFiltersCount = computed(() => {
      return Object.values(columnFilters).filter(value => value && value.toString().trim() !== '').length
    })

    const visiblePages = computed(() => {
      if (!pagination.value) return []
      
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const delta = 2 // Number of pages to show around current page
      
      const pages = []
      const left = current - delta
      const right = current + delta + 1
      
      for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= left && i < right)) {
          pages.push(i)
        }
      }
      
      // Add ellipsis
      const result = []
      let prev = 0
      
      for (const page of pages) {
        if (page - prev === 2) {
          result.push(prev + 1)
        } else if (page - prev !== 1) {
          result.push('...')
        }
        result.push(page)
        prev = page
      }
      
      return result
    })

    // Estados de loading calculados
    const isInitialLoading = computed(() => {
      // Si la prop loading está definida, tiene prioridad para loading inicial
      if (props.loading !== undefined) {
        return props.loading && !hasInitialLoad.value
      }
      // Si no hay metadatos cargados (columnas vacías o primer load), es loading inicial
      return internalLoading.value && !hasInitialLoad.value
    })

    const isDataLoading = computed(() => {
      // Si la prop loading está definida, tiene prioridad para loading de datos
      if (props.loading !== undefined) {
        return props.loading && hasInitialLoad.value
      }
      // Loading de datos solo después de la primera carga
      return dataLoading.value && hasInitialLoad.value
    })

    // Debounce helper
    function debounce(func, delay) {
      let timeoutId
      return function (...args) {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => func.apply(this, args), delay)
      }
    }



    // Methods
    const fetchData = async (resetPage = false) => {
      // Determinar tipo de loading
      if (!hasInitialLoad.value) {
        internalLoading.value = true
      } else {
        dataLoading.value = true
      }
      
      try {
        const params = {
          ...props.queryParams,
          per_page: perPage.value
        }

        // Add search
        if (searchTerm.value.trim()) {
          params.search = searchTerm.value.trim()
        }

        // Add sorting
        if (currentSort.value) {
          params.sortBy = currentSort.value
          params.order = sortOrder.value
        }

        // Add column filters
        Object.keys(columnFilters).forEach(columnKey => {
          if (columnFilters[columnKey] && columnFilters[columnKey].toString().trim() !== '') {
            const column = dynamicColumns.value.find(col => col.key === columnKey)
            let filterKey
            
            if (column && column.relation && column.relation_field && column.relation_table && column.relation_column) {
              // For relation filters, use format: f_user_id:user.name
              filterKey = `f_${column.relation_field}:${column.relation_table}.${column.relation_column}`
            } else if (column && column.key.startsWith('r:')) {
              // Handle existing r: format by converting to new format
              const relationPart = column.key.substring(2) // Remove 'r:'
              const [relationTable, relationColumn] = relationPart.split('.')
              const foreignKey = `${relationTable}_id` // Assume standard naming convention
              filterKey = `f_${foreignKey}:${relationTable}.${relationColumn}`
            } else if (column && column.type === 'search' && column.filterField) {
              // Para filtros tipo search, usar siempre filterField
              filterKey = `f_${column.filterField}`
            } else {
              filterKey = `f_${columnKey}`
            }
            
            params[filterKey] = columnFilters[columnKey]
          }
        })

        // Add pagination
        if (!resetPage && pagination.value) {
          params.page = pagination.value.current_page
        }

        const response = await axios.get(props.endpoint, { params })
        
        if (response.data.success) {
          data.value = response.data.data
          pagination.value = response.data.pagination
          
          // Marcar que ya se hizo la primera carga
          if (!hasInitialLoad.value) {
            hasInitialLoad.value = true
          }
          
          emit('dataLoaded', response.data)
        } else {
          throw new Error(response.data.message || 'Error al cargar datos')
        }
      } catch (error) {
        console.error('Error fetching data:', error)
        emit('error', error)
        data.value = []
        pagination.value = null
      } finally {
        internalLoading.value = false
        dataLoading.value = false
      }
    }

    const handleSearch = debounce(() => {
      fetchData(true)
    }, 300)

    const toggleSort = (column) => {
      const columnDef = dynamicColumns.value.find(col => col.key === column)
      if (!columnDef || !columnDef.sortable) return

      if (currentSort.value === column) {
        if (sortOrder.value === 'asc') {
          sortOrder.value = 'desc'
        } else if (sortOrder.value === 'desc') {
          currentSort.value = ''
          sortOrder.value = ''
        } else {
          sortOrder.value = 'asc'
        }
      } else {
        currentSort.value = column
        sortOrder.value = 'asc'
      }

      fetchData(true)
    }

    const handleColumnFilter = ({ column, value }) => {
      if (value && value.toString().trim() !== '') {
        columnFilters[column] = value
      } else {
        columnFilters[column] = null
      }
      fetchData(true)
    }

    const handleColumnFilterClear = ({ column }) => {
      columnFilters[column] = null
      fetchData(true)
    }

    const clearAllFilters = () => {
      Object.keys(columnFilters).forEach(key => {
        columnFilters[key] = null
      })
      fetchData(true)
    }

    const changePerPage = () => {
      fetchData(true)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page
        fetchData()
      }
    }

    const refreshData = () => {
      // Limpiar cache forzando una nueva petición
      fetchData(true)
    }

    // Alias para refresh
    const refresh = () => {
      refreshData()
    }

    // Método para refrescar completamente limpiando cache
    const hardRefresh = () => {
      // Limpiar datos actuales
      data.value = []
      pagination.value = null
      
      // Forzar recarga inicial
      hasInitialLoad.value = false
      
      // Cargar datos desde cero
      fetchData(true)
    }

    // Método para actualizar después de operaciones CRUD
    const updateAfterOperation = (operation = 'update') => {
      // Para operaciones de eliminación, hacer un hard refresh
      if (operation === 'delete') {
        hardRefresh()
      } else {
        // Para otras operaciones, refresh normal
        refreshData()
      }
    }

    const getNestedValue = (obj, path) => {
      return path.split('.').reduce((current, key) => {
        return current && current[key] !== undefined ? current[key] : null
      }, obj)
    }

    const formatCellValue = (value, column) => {
      if (value === null || value === undefined) return ''
      
      switch (column.type) {
        case 'date':
          return new Date(value).toLocaleDateString()
        case 'boolean':
          return value ? 'Sí' : 'No'
        default:
          return value
      }
    }

    // Lifecycle
    onMounted(async () => {
      // Cargar metadatos si se proporciona modelName
      if (props.modelName) {
        await loadTableMetadata()
      }
      
      // Inicializar filtros de columnas
      initializeColumnFilters()
      
      // Cargar datos si autoLoad está habilitado
      if (props.autoLoad) {
        fetchData()
      }
    })

    // Watch for prop changes
    watch(() => props.endpoint, () => {
      if (props.autoLoad) {
        fetchData(true)
      }
    })

    watch(() => props.queryParams, () => {
      if (props.autoLoad) {
        fetchData(true)
      }
    }, { deep: true })

    // Watch for metadata changes to reinitialize filters
    watch(() => tableMetadata.value, () => {
      if (tableMetadata.value) {
        initializeColumnFilters()
      }
    })

    // Formatear acciones para el dropdown
    const formatActionsForDropdown = (actions, item) => {
      return actions
        .filter(action => action.showInDropdown !== false) // Filtrar acciones que no deben aparecer en dropdown
        .map(action => ({
          key: action.key || action.name,
          label: action.label,
          icon: action.icon,
          color: action.color,
          type: action.type,
          modal: action.modal, // Referencia al modal correspondiente
          method: action.method,
          endpoint: action.endpoint,
          confirmMessage: action.confirmMessage,
          item: item // Pasar el item para tenerlo disponible en el click
        }))
    }

    // Manejar click en opción del dropdown
    const handleActionClick = (option) => {
      emit('action', { action: option.key, item: option.item })
    }

    return {
      // Data
      data,
      pagination,
      loading: isInitialLoading, // Cambiado para mantener compatibilidad
      searchTerm,
      currentSort,
      sortOrder,
      perPage,
      columnFilters,
      
      // Computed
      visibleColumns,
      activeFiltersCount,
      visiblePages,
      isInitialLoading,
      isDataLoading,
      
      // Dynamic metadata
      dynamicColumns,
      dynamicActions,
      dynamicSearchPlaceholder,
      tableMetadata,
      metadataLoading,
      
      // Methods
      fetchData,
      handleSearch,
      toggleSort,
      handleColumnFilter,
      handleColumnFilterClear,
      clearAllFilters,
      changePerPage,
      goToPage,
      refreshData,
      refresh, // Añadir alias refresh
      hardRefresh, // Añadir método de hard refresh
      updateAfterOperation, // Añadir método de actualización
      getNestedValue,
      formatCellValue,
      formatActionsForDropdown,
      handleActionClick,
      loadTableMetadata
    }
  }
}
</script>

<style scoped>
/* Custom scrollbar for filter dropdown */
.max-h-96::-webkit-scrollbar {
  width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style> 