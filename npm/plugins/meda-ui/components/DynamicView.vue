<template>
  <div class="dynamic-view">
    <!-- Loading inicial completo -->
    <div v-if="isInitialLoading" class="flex items-center justify-center min-h-[400px]">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p class="text-gray-600 dark:text-gray-400">Cargando vista dinámica...</p>
      </div>
    </div>

    <!-- Error de carga -->
    <div v-else-if="loadError" class="bg-red-50 border border-red-200 rounded-lg p-6">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <h3 class="text-sm font-medium text-red-800">Error al cargar la vista</h3>
      </div>
      <div class="mt-2 text-sm text-red-700">
        {{ loadError }}
      </div>
      <div class="mt-4">
        <button 
          @click="retry"
          class="bg-red-100 hover:bg-red-200 text-red-800 px-3 py-2 rounded text-sm font-medium"
        >
          Reintentar
        </button>
      </div>
    </div>

    <!-- Vista principal -->
    <div v-else-if="viewMetadata" class="space-y-6">
      <!-- Header dinámico -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ pageTitle }}
          </h1>
          <p v-if="pageDescription" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ pageDescription }}
          </p>
        </div>
        
        <!-- Botón de crear (si está disponible) -->
        <button
          v-if="createAction"
          @click="handleCreateAction"
          :class="[
            'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
            getButtonClasses(createAction.color)
          ]"
        >
          <i :class="createAction.icon + ' mr-2'"></i>
          {{ createAction.label }}
        </button>
      </div>

      <!-- Estadísticas (si están disponibles) -->
      <StatsGrid 
        v-if="stats && stats.length > 0"
        :stats="stats" 
        class="mb-6"
      />

      <!-- Tabla de datos -->
      <DataTable
        :endpoint="dataEndpoint"
        :model-name="modelName"
        :auto-load="true"
        @action="handleTableAction"
        @success="handleTableSuccess"
      >
        <!-- Slots dinámicos para celdas personalizadas -->
        <template v-for="slot in customSlots" :key="slot.name" #[slot.name]="slotProps">
          <component 
            :is="slot.component" 
            v-bind="slotProps" 
            v-bind="slot.props"
          />
        </template>
      </DataTable>

      <!-- Modal dinámico -->
      <DataModal
        v-model="showModal"
        :model-name="modelName"
        :modal-type="modalType"
        :modal-key="modalKey"
        :initial-data="modalData"
        @success="handleModalSuccess"
        @close="handleModalClose"
      />
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import DataTable from './DataTable/index.vue'
import DataModal from './DataModal.vue'
import StatsGrid from './StatsGrid.vue'

export default {
  name: 'DynamicView',
  components: {
    DataTable,
    DataModal,
    StatsGrid
  },
  props: {
    // Ruta base para las operaciones dinámicas
    basePath: {
      type: String,
      default: '/dynamic'
    },
    // Nombre del modelo (opcional, se puede obtener de la ruta)
    modelName: {
      type: String,
      default: null
    },
    // Configuración personalizada de la vista
    viewConfig: {
      type: Object,
      default: () => ({})
    },
    // Slots personalizados para celdas
    customSlots: {
      type: Array,
      default: () => []
    },
    // Si debe usar rutas dinámicas automáticas
    useAutoRoutes: {
      type: Boolean,
      default: true
    }
  },
  emits: ['loaded', 'error', 'action'],
  setup(props, { emit }) {
    const route = useRoute()
    const router = useRouter()

    // Estado
    const isInitialLoading = ref(true)
    const loadError = ref('')
    const viewMetadata = ref(null)
    const stats = ref([])
    
    // Modal state
    const showModal = ref(false)
    const modalType = ref('')
    const modalKey = ref('')
    const modalData = ref({})

    // Computed properties
    const currentModelName = computed(() => {
      return props.modelName || route.params.model || route.meta?.model
    })

    const dataEndpoint = computed(() => {
      if (props.useAutoRoutes) {
        return `${props.basePath}/${currentModelName.value}`
      }
      return props.viewConfig.endpoint || `/api/${currentModelName.value}`
    })

    const metadataEndpoint = computed(() => {
      if (props.useAutoRoutes) {
        return `${props.basePath}/${currentModelName.value}/metadata`
      }
      return `${dataEndpoint.value}/metadata`
    })

    const pageTitle = computed(() => {
      return props.viewConfig.title || 
             viewMetadata.value?.title || 
             `Gestión de ${currentModelName.value}`
    })

    const pageDescription = computed(() => {
      return props.viewConfig.description || 
             viewMetadata.value?.description || 
             null
    })

    const createAction = computed(() => {
      return viewMetadata.value?.createModal || null
    })

    // Cargar metadatos de la vista
    const loadViewMetadata = async () => {
      try {
        isInitialLoading.value = true
        loadError.value = ''

        const response = await axios.get(metadataEndpoint.value)
        
        if (response.data.success) {
          viewMetadata.value = response.data.data
          
          // Cargar estadísticas si están disponibles
          if (viewMetadata.value.hasStats) {
            await loadStats()
          }
          
          emit('loaded', viewMetadata.value)
        } else {
          throw new Error(response.data.message || 'Error al cargar metadatos')
        }
      } catch (error) {
        console.error('Error loading view metadata:', error)
        loadError.value = error.response?.data?.message || error.message || 'Error desconocido'
        emit('error', error)
      } finally {
        isInitialLoading.value = false
      }
    }

    // Cargar estadísticas
    const loadStats = async () => {
      try {
        const response = await axios.get(`${dataEndpoint.value}/stats`)
        if (response.data.success) {
          stats.value = response.data.data
        }
      } catch (error) {
        console.warn('Stats not available:', error)
      }
    }

    // Manejar acción de crear
    const handleCreateAction = () => {
      modalType.value = 'form'
      modalKey.value = 'create'
      modalData.value = {}
      showModal.value = true
    }

    // Manejar acciones de la tabla
    const handleTableAction = ({ action, item }) => {
      // Emitir evento para personalización externa
      emit('action', { action, item })

      // Manejar acciones estándar
      if (['view', 'edit', 'delete'].includes(action)) {
        modalType.value = action === 'delete' ? 'confirm' : action
        modalKey.value = action
        modalData.value = item
        showModal.value = true
      } else {
        // Acción personalizada - manejar con endpoint dinámico
        handleCustomAction(action, item)
      }
    }

    // Manejar acciones personalizadas
    const handleCustomAction = async (action, item) => {
      try {
        let endpoint
        
        if (props.useAutoRoutes) {
          endpoint = `${props.basePath}/${currentModelName.value}/${item.id}/${action}`
        } else {
          endpoint = `${dataEndpoint.value}/${item.id}/${action}`
        }

        const response = await axios.post(endpoint)
        
        if (response.data.success) {
          // Refrescar tabla si es necesario
          handleTableSuccess()
        }
      } catch (error) {
        console.error('Error executing custom action:', error)
      }
    }

    // Manejar éxito del modal
    const handleModalSuccess = (data) => {
      showModal.value = false
      handleTableSuccess()
    }

    // Manejar cierre del modal
    const handleModalClose = () => {
      showModal.value = false
      modalData.value = {}
    }

    // Manejar éxito de la tabla
    const handleTableSuccess = () => {
      // Recargar estadísticas si están disponibles
      if (viewMetadata.value?.hasStats) {
        loadStats()
      }
    }

    // Reintentar carga
    const retry = () => {
      loadViewMetadata()
    }

    // Obtener clases CSS para botones según el color
    const getButtonClasses = (color) => {
      const colorMap = {
        primary: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        success: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
        warning: 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
        danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        info: 'bg-blue-500 hover:bg-blue-600 text-white focus:ring-blue-400',
        secondary: 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500'
      }
      
      return colorMap[color] || colorMap.primary
    }

    // Watchers
    watch(() => currentModelName.value, () => {
      if (currentModelName.value) {
        loadViewMetadata()
      }
    }, { immediate: true })

    return {
      // Estado
      isInitialLoading,
      loadError,
      viewMetadata,
      stats,
      showModal,
      modalType,
      modalKey,
      modalData,

      // Computed
      currentModelName,
      dataEndpoint,
      pageTitle,
      pageDescription,
      createAction,

      // Métodos
      handleCreateAction,
      handleTableAction,
      handleModalSuccess,
      handleModalClose,
      handleTableSuccess,
      retry,
      getButtonClasses
    }
  }
}
</script>

<style scoped>
.dynamic-view {
  @apply min-h-screen bg-gray-50 dark:bg-gray-900;
}

/* Animaciones para transiciones suaves */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>