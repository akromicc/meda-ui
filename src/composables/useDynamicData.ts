import { ref, reactive, computed } from 'vue'
import axios from 'axios'
import type { DynamicApiResponse, DynamicMetadata } from '../types'
import { useDynamicUI } from './useDynamicUI'

export function useDynamicData(endpoint: string, options: any = {}) {
  const { config, state, setLoading, setError, setData, buildUrl } = useDynamicUI()

  // Estado específico para datos
  const metadata = ref<DynamicMetadata | null>(null)
  const cache = new Map<string, any>()

  // Opciones por defecto
  const defaultOptions = {
    autoLoad: true,
    cacheEnabled: true,
    cacheTimeout: config.cacheTimeout,
    relations: [],
    searchColumns: [],
    customQuery: null,
    perPage: config.pagination.perPage,
    defaultSortBy: 'created_at',
    defaultOrder: 'desc' as 'asc' | 'desc',
    additionalData: {}
  }

  const mergedOptions = { ...defaultOptions, ...options }

  // Computed properties
  const hasMetadata = computed(() => !!metadata.value)
  const tableConfig = computed(() => metadata.value?.table)
  const modalConfig = computed(() => metadata.value?.modal)

  // Métodos para cargar metadatos
  const loadMetadata = async (modelName?: string) => {
    try {
      setLoading(true)
      
      const modelEndpoint = modelName || endpoint.split('/').pop()
      const metadataUrl = `${config.metadataEndpoint}/${modelEndpoint}`
      
      // Verificar caché
      if (mergedOptions.cacheEnabled && cache.has('metadata')) {
        const cached = cache.get('metadata')
        if (Date.now() - cached.timestamp < mergedOptions.cacheTimeout) {
          metadata.value = cached.data
          setLoading(false)
          return cached.data
        }
      }

      const response = await axios.get(metadataUrl)
      
      if (response.data.success) {
        metadata.value = response.data.data
        
        // Guardar en caché
        if (mergedOptions.cacheEnabled) {
          cache.set('metadata', {
            data: response.data.data,
            timestamp: Date.now()
          })
        }
        
        setLoading(false)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Error al cargar metadatos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al cargar metadatos')
      throw error
    }
  }

  // Métodos para cargar datos
  const loadData = async (page?: number) => {
    try {
      setLoading(true)
      
      const url = buildUrl(endpoint, page)
      const response = await axios.get(url)
      
      if (response.data.success) {
        setData(response.data)
        return response.data
      } else {
        throw new Error(response.data.message || 'Error al cargar datos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al cargar datos')
      throw error
    }
  }

  // Métodos para crear datos
  const createData = async (data: any) => {
    try {
      setLoading(true)
      
      const response = await axios.post(endpoint, data)
      
      if (response.data.success) {
        // Recargar datos después de crear
        await loadData()
        return response.data
      } else {
        throw new Error(response.data.message || 'Error al crear datos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al crear datos')
      throw error
    }
  }

  // Métodos para actualizar datos
  const updateData = async (id: number, data: any) => {
    try {
      setLoading(true)
      
      const response = await axios.put(`${endpoint}/${id}`, data)
      
      if (response.data.success) {
        // Recargar datos después de actualizar
        await loadData()
        return response.data
      } else {
        throw new Error(response.data.message || 'Error al actualizar datos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al actualizar datos')
      throw error
    }
  }

  // Métodos para eliminar datos
  const deleteData = async (id: number) => {
    try {
      setLoading(true)
      
      const response = await axios.delete(`${endpoint}/${id}`)
      
      if (response.data.success) {
        // Recargar datos después de eliminar
        await loadData()
        return response.data
      } else {
        throw new Error(response.data.message || 'Error al eliminar datos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al eliminar datos')
      throw error
    }
  }

  // Métodos para obtener un elemento específico
  const getData = async (id: number) => {
    try {
      setLoading(true)
      
      const response = await axios.get(`${endpoint}/${id}`)
      
      if (response.data.success) {
        setLoading(false)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Error al obtener datos')
      }
    } catch (error: any) {
      setError(error.message || 'Error al obtener datos')
      throw error
    }
  }

  // Métodos para obtener formulario de creación
  const getCreateForm = async () => {
    try {
      setLoading(true)
      
      const response = await axios.get(`${endpoint}/create`)
      
      if (response.data.success) {
        setLoading(false)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Error al obtener formulario')
      }
    } catch (error: any) {
      setError(error.message || 'Error al obtener formulario')
      throw error
    }
  }

  // Métodos para obtener formulario de edición
  const getEditForm = async (id: number) => {
    try {
      setLoading(true)
      
      const response = await axios.get(`${endpoint}/${id}/edit`)
      
      if (response.data.success) {
        setLoading(false)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Error al obtener formulario')
      }
    } catch (error: any) {
      setError(error.message || 'Error al obtener formulario')
      throw error
    }
  }

  // Métodos para búsqueda
  const searchData = async (query: string) => {
    try {
      setLoading(true)
      
      const response = await axios.get(`${endpoint}/search`, {
        params: { q: query }
      })
      
      if (response.data.success) {
        setLoading(false)
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Error en la búsqueda')
      }
    } catch (error: any) {
      setError(error.message || 'Error en la búsqueda')
      throw error
    }
  }

  // Métodos para acciones personalizadas
  const executeAction = async (id: number, action: string, data?: any) => {
    try {
      setLoading(true)
      
      const response = await axios.post(`${endpoint}/${id}/${action}`, data)
      
      if (response.data.success) {
        // Recargar datos después de la acción
        await loadData()
        return response.data
      } else {
        throw new Error(response.data.message || 'Error al ejecutar acción')
      }
    } catch (error: any) {
      setError(error.message || 'Error al ejecutar acción')
      throw error
    }
  }

  // Métodos para limpiar caché
  const clearCache = () => {
    cache.clear()
  }

  const clearMetadataCache = () => {
    cache.delete('metadata')
  }

  // Cargar datos iniciales si autoLoad está habilitado
  if (mergedOptions.autoLoad) {
    loadMetadata()
    loadData()
  }

  return {
    // Estado
    metadata,
    state,
    
    // Computed properties
    hasMetadata,
    tableConfig,
    modalConfig,
    
    // Métodos de datos
    loadMetadata,
    loadData,
    createData,
    updateData,
    deleteData,
    getData,
    getCreateForm,
    getEditForm,
    searchData,
    executeAction,
    
    // Métodos de caché
    clearCache,
    clearMetadataCache
  }
}