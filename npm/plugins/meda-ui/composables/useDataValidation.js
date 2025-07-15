import axios from '../plugins/axios.js'
import { error } from '../plugins/toast.js'

export function useDataValidation() {
  /**
   * Valida que un elemento existe en el backend
   * @param {string} endpoint - Endpoint base (ej: '/api/devices')
   * @param {Object} item - Elemento a validar
   * @param {Function} onNotFound - Callback cuando el elemento no existe
   * @returns {boolean} - True si el elemento existe
   */
  const validateItemExists = async (endpoint, item, onNotFound = null) => {
    try {
      // Hacer petición al backend
      await axios.get(`${endpoint}/${item.id}`)
      return true
    } catch (err) {
      if (err.isModelNotFound || err.response?.status === 404) {
        error('Elemento no encontrado', 'El elemento que intentas acceder ya no existe')
        
        // Ejecutar callback si se proporciona
        if (onNotFound) {
          onNotFound(item)
        }
        
        return false
      }
      
      // Si es otro tipo de error, re-lanzarlo
      throw err
    }
  }

  /**
   * Valida múltiples elementos
   * @param {string} endpoint - Endpoint base
   * @param {Array} items - Array de elementos a validar
   * @param {Function} onNotFound - Callback cuando algún elemento no existe
   * @returns {Array} - Array de elementos que existen
   */
  const validateMultipleItems = async (endpoint, items, onNotFound = null) => {
    const validItems = []
    
    for (const item of items) {
      const exists = await validateItemExists(endpoint, item, onNotFound)
      if (exists) {
        validItems.push(item)
      }
    }
    
    return validItems
  }

  return {
    validateItemExists,
    validateMultipleItems
  }
} 