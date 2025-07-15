// Plugin principal de meda-ui
// Sistema súper simple para tablas y modales dinámicos

// Importar todos los componentes
import MedaDataTable from './components/DataTable/index.vue'
import MedaDataModal from './components/DataModal.vue'
import MedaStatsGrid from './components/StatsGrid.vue'
import MedaStatCard from './components/StatCard.vue'
import Skeleton from './components/Skeleton.vue'

// Importar composables
import { useMetadata } from './composables/useMetadata.js'

// Plugin de instalación
const MedaUI = {
  install(app, options = {}) {
    // Registrar componentes globalmente
    app.component('MedaDataTable', MedaDataTable)
    app.component('MedaDataModal', MedaDataModal)
    app.component('MedaStatsGrid', MedaStatsGrid)
    app.component('MedaStatCard', MedaStatCard)
    app.component('Skeleton', Skeleton)
    
    // Configuración global del plugin
    const config = {
      // Cache timeout por defecto (1 hora)
      cacheTimeout: options.cacheTimeout || 60 * 60 * 1000,
      // Endpoint base para metadatos
      metadataEndpoint: options.metadataEndpoint || '/api/metadata',
      // Configuración de axios (si se necesita)
      axios: options.axios || null,
      ...options
    }
    
    // Hacer configuración disponible globalmente
    app.config.globalProperties.$medaUI = config
    app.provide('medaUIConfig', config)
    
    console.log('🚀 MedaUI Plugin instalado correctamente')
  }
}

// Exportar para uso directo
export { useMetadata }

// Exportar plugin por defecto
export default MedaUI