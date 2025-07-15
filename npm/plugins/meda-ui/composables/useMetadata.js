import { ref } from 'vue'
import axios from '../../../plugins/axios.js'

// Cache global para metadatos
const metadataCache = new Map()
const cacheTimeout = 60 * 60 * 1000 // 1 hora en milisegundos

/**
 * Composable para manejar metadatos de tablas y modales
 * Sistema súper simple que se conecta al MetadataController
 */
export function useMetadata() {
  const loading = ref(false)
  const error = ref(null)

  /**
   * Obtener metadatos de tabla para un modelo específico
   */
  const getTableMetadata = async (model) => {
    const cacheKey = `table_${model}`
    
    // Verificar cache
    if (metadataCache.has(cacheKey)) {
      const cached = metadataCache.get(cacheKey)
      if (Date.now() - cached.timestamp < cacheTimeout) {
        return cached.data
      }
    }

    loading.value = true
    error.value = null

    try {
      const response = await axios.get(`/api/metadata/table/${model}`)
      
      if (response.data.success) {
        const metadata = response.data.data
        
        // Guardar en cache
        metadataCache.set(cacheKey, {
          data: metadata,
          timestamp: Date.now()
        })
        
        return metadata
      } else {
        throw new Error(response.data.message || 'Error al cargar metadatos de tabla')
      }
    } catch (err) {
      error.value = err.message || 'Error de conexión'
      console.error('Error loading table metadata:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Obtener metadatos de modal para un modelo específico
   */
  const getModalMetadata = async (model) => {
    const cacheKey = `modal_${model}`
    
    // Verificar cache
    if (metadataCache.has(cacheKey)) {
      const cached = metadataCache.get(cacheKey)
      if (Date.now() - cached.timestamp < cacheTimeout) {
        return cached.data
      }
    }

    loading.value = true
    error.value = null

    try {
      const response = await axios.get(`/api/metadata/modal/${model}`)
      
      if (response.data.success) {
        const metadata = response.data.data
        
        // Guardar en cache
        metadataCache.set(cacheKey, {
          data: metadata,
          timestamp: Date.now()
        })
        
        return metadata
      } else {
        throw new Error(response.data.message || 'Error al cargar metadatos de modal')
      }
    } catch (err) {
      error.value = err.message || 'Error de conexión'
      console.error('Error loading modal metadata:', err)
      return null
    } finally {
      loading.value = false
    }
  }

  /**
   * Obtener todos los metadatos de una vez (optimización)
   */
  const getAllMetadata = async () => {
    const cacheKey = 'all_metadata'
    
    // Verificar cache
    if (metadataCache.has(cacheKey)) {
      const cached = metadataCache.get(cacheKey)
      if (Date.now() - cached.timestamp < cacheTimeout) {
        return cached.data
      }
    }

    loading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/metadata')
      
      if (response.data.success) {
        const metadata = response.data.data
        
        // Guardar en cache global
        metadataCache.set(cacheKey, {
          data: metadata,
          timestamp: Date.now()
        })
        
        // También guardar individualmente para acceso rápido
        if (metadata.tables) {
          Object.keys(metadata.tables).forEach(model => {
            metadataCache.set(`table_${model}`, {
              data: metadata.tables[model],
              timestamp: Date.now()
            })
          })
        }
        
        if (metadata.modals) {
          Object.keys(metadata.modals).forEach(model => {
            metadataCache.set(`modal_${model}`, {
              data: metadata.modals[model],
              timestamp: Date.now()
            })
          })
        }
        
        return metadata
      } else {
        throw new Error(response.data.message || 'Error al cargar metadatos')
      }
    } catch (err) {
      error.value = err.message || 'Error de conexión'
      console.error('Error loading all metadata:', err)
      return { tables: {}, modals: {} }
    } finally {
      loading.value = false
    }
  }

  /**
   * Limpiar cache de metadatos
   */
  const clearCache = () => {
    metadataCache.clear()
  }

  /**
   * Limpiar cache de metadatos en el servidor
   */
  const clearServerCache = async () => {
    try {
      await axios.delete('/api/metadata/cache')
      clearCache() // También limpiar cache local
      return true
    } catch (err) {
      console.error('Error clearing server cache:', err)
      return false
    }
  }

  /**
   * Verificar si hay metadatos en cache para un modelo
   */
  const hasTableMetadata = (model) => {
    const cacheKey = `table_${model}`
    if (!metadataCache.has(cacheKey)) return false
    
    const cached = metadataCache.get(cacheKey)
    return Date.now() - cached.timestamp < cacheTimeout
  }

  /**
   * Verificar si hay metadatos de modal en cache para un modelo
   */
  const hasModalMetadata = (model) => {
    const cacheKey = `modal_${model}`
    if (!metadataCache.has(cacheKey)) return false
    
    const cached = metadataCache.get(cacheKey)
    return Date.now() - cached.timestamp < cacheTimeout
  }

  /**
   * Precargar metadatos para múltiples modelos
   */
  const preloadMetadata = async (models) => {
    const promises = []
    
    models.forEach(model => {
      if (!hasTableMetadata(model)) {
        promises.push(getTableMetadata(model))
      }
      if (!hasModalMetadata(model)) {
        promises.push(getModalMetadata(model))
      }
    })
    
    if (promises.length > 0) {
      await Promise.allSettled(promises)
    }
  }

  return {
    // Estado
    loading,
    error,
    
    // Métodos principales
    getTableMetadata,
    getModalMetadata,
    getAllMetadata,
    
    // Gestión de cache
    clearCache,
    clearServerCache,
    hasTableMetadata,
    hasModalMetadata,
    preloadMetadata
  }
}