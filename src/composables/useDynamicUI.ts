import { ref, reactive, computed, inject } from 'vue'
import type { DynamicUIConfig, DynamicLoadingState, DynamicApiResponse } from '../types'

export function useDynamicUI() {
  // Obtener configuración global
  const config = inject<DynamicUIConfig>('dynamicUIConfig', {
    cacheTimeout: 60 * 60 * 1000,
    metadataEndpoint: '/api/metadata',
    dataEndpoint: '/api',
    pagination: {
      perPage: 15,
      perPageOptions: [10, 15, 25, 50, 100]
    },
    filters: {
      enabled: true,
      prefix: 'f_'
    },
    search: {
      enabled: true,
      placeholder: 'Buscar...'
    }
  })

  // Estado de carga
  const state = reactive<DynamicLoadingState>({
    loading: false,
    error: null,
    data: null,
    pagination: null,
    filters: {},
    search: '',
    sortBy: 'created_at',
    order: 'desc',
    perPage: config.pagination.perPage,
    selectedItems: []
  })

  // Computed properties
  const isLoading = computed(() => state.loading)
  const hasError = computed(() => !!state.error)
  const hasData = computed(() => !!state.data && state.data.length > 0)
  const totalItems = computed(() => state.pagination?.total || 0)
  const currentPage = computed(() => state.pagination?.current_page || 1)
  const lastPage = computed(() => state.pagination?.last_page || 1)
  const hasMorePages = computed(() => state.pagination?.has_more_pages || false)

  // Métodos para manejar estado
  const setLoading = (loading: boolean) => {
    state.loading = loading
    if (loading) {
      state.error = null
    }
  }

  const setError = (error: string) => {
    state.error = error
    state.loading = false
  }

  const setData = (response: DynamicApiResponse) => {
    state.data = response.data
    state.pagination = response.pagination || null
    state.error = null
    state.loading = false
  }

  const setFilters = (filters: Record<string, any>) => {
    state.filters = { ...state.filters, ...filters }
  }

  const setSearch = (search: string) => {
    state.search = search
  }

  const setSorting = (sortBy: string, order: 'asc' | 'desc') => {
    state.sortBy = sortBy
    state.order = order
  }

  const setPerPage = (perPage: number) => {
    state.perPage = perPage
  }

  const setSelectedItems = (items: any[]) => {
    state.selectedItems = items
  }

  const clearFilters = () => {
    state.filters = {}
  }

  const clearSearch = () => {
    state.search = ''
  }

  const clearSelection = () => {
    state.selectedItems = []
  }

  const resetState = () => {
    state.loading = false
    state.error = null
    state.data = null
    state.pagination = null
    state.filters = {}
    state.search = ''
    state.sortBy = 'created_at'
    state.order = 'desc'
    state.perPage = config.pagination.perPage
    state.selectedItems = []
  }

  // Métodos para construir URLs
  const buildQueryParams = () => {
    const params = new URLSearchParams()

    // Agregar búsqueda
    if (state.search) {
      params.append('search', state.search)
    }

    // Agregar ordenamiento
    if (state.sortBy) {
      params.append('sortBy', state.sortBy)
      params.append('order', state.order)
    }

    // Agregar paginación
    if (state.perPage) {
      params.append('per_page', state.perPage.toString())
    }

    // Agregar filtros
    Object.entries(state.filters).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        params.append(`${config.filters.prefix}${key}`, value.toString())
      }
    })

    return params.toString()
  }

  const buildUrl = (endpoint: string, page?: number) => {
    const baseUrl = endpoint.startsWith('http') ? endpoint : `${config.dataEndpoint}${endpoint}`
    const params = buildQueryParams()
    
    if (page && page > 1) {
      params ? params += `&page=${page}` : `page=${page}`
    }

    return params ? `${baseUrl}?${params}` : baseUrl
  }

  // Métodos para navegación
  const goToPage = (page: number) => {
    if (page >= 1 && page <= lastPage.value) {
      return buildUrl('', page)
    }
    return null
  }

  const goToNextPage = () => {
    if (hasMorePages.value) {
      return goToPage(currentPage.value + 1)
    }
    return null
  }

  const goToPrevPage = () => {
    if (currentPage.value > 1) {
      return goToPage(currentPage.value - 1)
    }
    return null
  }

  // Métodos para filtros
  const addFilter = (key: string, value: any) => {
    state.filters[key] = value
  }

  const removeFilter = (key: string) => {
    delete state.filters[key]
  }

  const hasFilter = (key: string) => {
    return key in state.filters
  }

  const getFilter = (key: string) => {
    return state.filters[key]
  }

  // Métodos para selección
  const selectItem = (item: any) => {
    if (!state.selectedItems.find(i => i.id === item.id)) {
      state.selectedItems.push(item)
    }
  }

  const deselectItem = (item: any) => {
    state.selectedItems = state.selectedItems.filter(i => i.id !== item.id)
  }

  const selectAll = (items: any[]) => {
    state.selectedItems = [...items]
  }

  const deselectAll = () => {
    state.selectedItems = []
  }

  const isSelected = (item: any) => {
    return state.selectedItems.some(i => i.id === item.id)
  }

  // Métodos para acciones
  const handleAction = (action: string, item: any) => {
    // Emitir evento de acción
    return { action, item }
  }

  const handleBulkAction = (action: string, items: any[]) => {
    // Emitir evento de acción masiva
    return { action, items }
  }

  return {
    // Configuración
    config,
    
    // Estado
    state,
    
    // Computed properties
    isLoading,
    hasError,
    hasData,
    totalItems,
    currentPage,
    lastPage,
    hasMorePages,
    
    // Métodos de estado
    setLoading,
    setError,
    setData,
    setFilters,
    setSearch,
    setSorting,
    setPerPage,
    setSelectedItems,
    clearFilters,
    clearSearch,
    clearSelection,
    resetState,
    
    // Métodos de URL
    buildQueryParams,
    buildUrl,
    
    // Métodos de navegación
    goToPage,
    goToNextPage,
    goToPrevPage,
    
    // Métodos de filtros
    addFilter,
    removeFilter,
    hasFilter,
    getFilter,
    
    // Métodos de selección
    selectItem,
    deselectItem,
    selectAll,
    deselectAll,
    isSelected,
    
    // Métodos de acciones
    handleAction,
    handleBulkAction
  }
}