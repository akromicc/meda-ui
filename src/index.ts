import type { App } from 'vue'
import DynamicDataTable from './components/DynamicDataTable.vue'
import DynamicDataModal from './components/DynamicDataModal.vue'
import DynamicStatsGrid from './components/DynamicStatsGrid.vue'
import DynamicStatCard from './components/DynamicStatCard.vue'
import DynamicSkeleton from './components/DynamicSkeleton.vue'

// Exportar componentes individuales
export {
  DynamicDataTable,
  DynamicDataModal,
  DynamicStatsGrid,
  DynamicStatCard,
  DynamicSkeleton
}

// Exportar composables
export { useDynamicUI } from './composables/useDynamicUI'
export { useDynamicData } from './composables/useDynamicData'
export { useDynamicMetadata } from './composables/useDynamicMetadata'

// Exportar tipos
export type {
  DynamicTableConfig,
  DynamicModalConfig,
  DynamicColumn,
  DynamicField,
  DynamicPagination,
  DynamicFilter
} from './types'

// Plugin de instalación
const DynamicUI = {
  install(app: App, options: any = {}) {
    // Registrar componentes globalmente
    app.component('DynamicDataTable', DynamicDataTable)
    app.component('DynamicDataModal', DynamicDataModal)
    app.component('DynamicStatsGrid', DynamicStatsGrid)
    app.component('DynamicStatCard', DynamicStatCard)
    app.component('DynamicSkeleton', DynamicSkeleton)
    
    // Configuración global del plugin
    const config = {
      // Cache timeout por defecto (1 hora)
      cacheTimeout: options.cacheTimeout || 60 * 60 * 1000,
      // Endpoint base para metadatos
      metadataEndpoint: options.metadataEndpoint || '/api/metadata',
      // Endpoint base para datos
      dataEndpoint: options.dataEndpoint || '/api',
      // Configuración de axios
      axios: options.axios || null,
      // Configuración de paginación
      pagination: {
        perPage: options.pagination?.perPage || 15,
        perPageOptions: options.pagination?.perPageOptions || [10, 15, 25, 50, 100]
      },
      // Configuración de filtros
      filters: {
        enabled: options.filters?.enabled !== false,
        prefix: options.filters?.prefix || 'f_'
      },
      // Configuración de búsqueda
      search: {
        enabled: options.search?.enabled !== false,
        placeholder: options.search?.placeholder || 'Buscar...'
      },
      ...options
    }
    
    // Hacer configuración disponible globalmente
    app.config.globalProperties.$dynamicUI = config
    app.provide('dynamicUIConfig', config)
    
    console.log('🚀 DynamicUI Plugin instalado correctamente')
  }
}

// Exportar plugin por defecto
export default DynamicUI