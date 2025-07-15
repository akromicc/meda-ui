<template>
  <Modal v-model="isOpen" :size="size" @close="handleClose">
    <template #header>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">
        {{ internalTitle }}
      </h3>
    </template>

    <template #body>
      <!-- Para formularios (create, edit, custom) -->
      <div v-if="showForm">
        <!-- Mensaje de error -->
        <div v-if="error" class="rounded-md bg-danger-50 dark:bg-danger-900/20 p-4 mb-4">
          <div class="text-sm text-danger-700 dark:text-danger-400">
            {{ error }}
          </div>
        </div>

        <!-- Campos del formulario -->
        <div :class="[
          'grid gap-x-6 gap-y-4',
          internalColumns === 1 ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2'
        ]">
          <div v-for="field in formFields" :key="field.key" class="space-y-2">
          <label :for="field.key" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ field.label }}
            <span v-if="field.required" class="text-danger-500">*</span>
          </label>

          <!-- Text, Email, Number, Password -->
          <input
            v-if="['text', 'email', 'number', 'password'].includes(field.type)"
            :id="field.key"
            v-model="formData[field.key]"
            :type="field.type"
            :placeholder="field.placeholder"
            :required="field.required"
            class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
          />

          <!-- Boolean/Checkbox -->
          <div v-else-if="field.type === 'boolean'" class="flex items-center">
            <input
              :id="field.key"
              v-model="formData[field.key]"
              type="checkbox"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded"
            />
            <label :for="field.key" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
              {{ field.checkboxLabel || field.label }}
            </label>
          </div>

          <!-- Texto de ayuda -->
          <p v-if="field.help" class="text-xs text-gray-500 dark:text-gray-400">
            {{ field.help }}
          </p>
          </div>
        </div>
      </div>

      <!-- Para vista (view) - Solo lectura -->
      <div v-else-if="showView">
        <div :class="[
          'grid gap-4',
          internalColumns === 1 ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2'
        ]">
          <div v-for="field in viewFields" :key="field.key" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
              {{ field.label }}
            </label>
            
            <!-- QR Code -->
            <div v-if="field.type === 'qr'" class="text-center">
              <div v-if="loading" class="text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center justify-center space-x-2">
                  <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                  <span>{{ internalLoadingText }}</span>
                </div>
              </div>
              <div v-else-if="internalInitialData[field.key]" class="relative">
                <img 
                  :src="internalInitialData[field.key]" 
                  :alt="field.label"
                  class="mx-auto max-w-xs rounded-lg shadow-lg"
                />
                <!-- Indicador de auto-refresh -->
                <div v-if="isMonitoringWhatsApp && internalModalType === 'qr_view'" class="absolute top-2 right-2">
                  <div class="flex items-center space-x-1 bg-blue-100 dark:bg-blue-900/20 px-2 py-1 rounded-full text-xs text-blue-700 dark:text-blue-300">
                    <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600"></div>
                    <span>Auto</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                QR no disponible
              </div>
              <p v-if="field.description" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ field.description }}
              </p>
              <!-- Información adicional para QR -->
              <div v-if="internalModalType === 'qr_view' && qrUpdateCount > 0" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                <p>QR actualizado {{ qrUpdateCount }} veces</p>
              </div>
            </div>
            
            <!-- Boolean/Checkbox - Solo texto -->
            <div v-else-if="field.type === 'boolean'" class="text-sm font-medium text-gray-900 dark:text-white">
              {{ internalInitialData[field.key] ? 'Sí' : 'No' }}
            </div>
            
            <!-- Otros tipos - Solo texto -->
            <div v-else class="text-sm font-medium text-gray-900 dark:text-white">
              {{ internalInitialData[field.key] || '-' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Para confirmaciones (delete, confirm) -->
      <div v-else-if="showConfirmation" class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-danger-100 dark:bg-danger-900/20">
          <svg class="h-6 w-6 text-danger-600 dark:text-danger-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ internalConfirmTitle || '¿Estás seguro?' }}
          </h3>
          <div class="mt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ internalConfirmMessage || 'Esta acción no se puede deshacer.' }}
            </p>
            <p v-if="itemDisplayText" class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
              {{ itemDisplayText }}
            </p>
          </div>
        </div>
      </div>

      <!-- Contenido personalizado via slot -->
      <div v-else>
        <slot name="content" :data="formData" :loading="loading" :error="error"></slot>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end space-x-3">
        <button
          type="button"
          @click="handleClose"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
        >
          {{ showView ? 'Cerrar' : 'Cancelar' }}
        </button>

        <!-- Solo mostrar botón de acción si NO es modo view -->
        <button
          v-if="!showView"
          type="button"
          @click="handleSubmit"
          :disabled="loading"
          :class="[
            'px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50',
            confirmAction ? 'bg-danger-600 hover:bg-danger-700 focus:ring-danger-500' : 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500'
          ]"
        >
          <span v-if="loading">{{ internalLoadingText }}</span>
          <span v-else>{{ internalSubmitText || 'Guardar' }}</span>
        </button>
      </div>
    </template>
  </Modal>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import Modal from './Modal.vue'
import { useMetadata } from '../composables/useMetadata.js'
import { useWhatsAppStatus } from '../../../composables/useWhatsAppStatus.js'

export default {
  name: 'DataModal',
  components: {
    Modal
  },
  props: {
    // Configuración básica
    fields: {
      type: Array,
      default: () => []
    },
    // Model name for automatic metadata loading
    modelName: {
      type: String,
      default: null
    },
    itemDisplayField: {
      type: String,
      default: 'name'
    },
    size: {
      type: String,
      default: 'lg'
    }
  },
  emits: ['close', 'success', 'error'],
  setup(props, { emit }) {
    const { getModalMetadata } = useMetadata()
    
    const isOpen = ref(false)
    const loading = ref(false)
    const error = ref('')
    const formData = reactive({})

    // Metadatos dinámicos
    const modalMetadata = ref(null)
    const metadataLoading = ref(false)

    // Computed properties para metadatos dinámicos
    const dynamicFields = computed(() => {
      if (props.modelName && modalMetadata.value?.fields) {
        return modalMetadata.value.fields
      }
      return props.fields
    })

    // Cargar metadatos si se proporciona modelName
    const loadModalMetadata = async () => {
      if (!props.modelName) return
      
      metadataLoading.value = true
      try {
        const metadata = await getModalMetadata(props.modelName)
        if (metadata) {
          modalMetadata.value = metadata
        }
      } catch (error) {
        console.error(`Error loading modal metadata for ${props.modelName}:`, error)
      } finally {
        metadataLoading.value = false
      }
    }

    const showForm = computed(() => internalActionType.value === 'form')
    const showView = computed(() => internalActionType.value === 'view')
    const showConfirmation = computed(() => internalActionType.value === 'confirm')
    const confirmAction = computed(() => internalActionType.value === 'confirm')
    
    const formFields = computed(() => {
      return dynamicFields.value.filter(field => 
        !field.hideInForm && 
        field.key !== 'id' && 
        field.key !== 'created_at' && 
        field.key !== 'updated_at'
      )
    })

    const viewFields = computed(() => {
      // Si es modal de QR, usar campos específicos
      if (internalModalType.value === 'qr_view') {
        console.log('🔍 Modal tipo QR detectado, usando campos específicos')
        return [
          {
            key: 'qr',
            label: 'Código QR',
            type: 'qr',
            description: 'Escanea este código QR con tu aplicación de WhatsApp'
          },
          {
            key: 'session_id',
            label: 'ID de Sesión',
            type: 'text'
          },
          {
            key: 'status',
            label: 'Estado',
            type: 'text'
          }
        ]
      }
      
      // Si hay campos personalizados, usarlos
      if (internalCustomFields.value.length > 0) {
        console.log('🔍 Usando campos personalizados:', internalCustomFields.value)
        return internalCustomFields.value
      }
      
      // Si no, usar los campos dinámicos
      console.log('🔍 Usando campos dinámicos del modelo')
      return dynamicFields.value.filter(field => 
        !field.hideInView &&
        field.key !== 'created_at' && 
        field.key !== 'updated_at'
      )
    })

    const itemDisplayText = computed(() => {
      if (internalInitialData.value && internalInitialData.value[props.itemDisplayField]) {
        return `"${internalInitialData.value[props.itemDisplayField]}"`
      }
      return ''
    })

    // Estados internos para la configuración del modal
    const internalTitle = ref('')
    const internalActionType = ref('form')
    const internalEndpoint = ref('')
    const internalMethod = ref('POST')
    const internalInitialData = ref({})
    const internalSubmitText = ref('')
    const internalLoadingText = ref('')
    const internalConfirmTitle = ref('')
    const internalConfirmMessage = ref('')
    const internalColumns = ref(2)
    const internalCustomFields = ref([])
    const internalModalType = ref('')
    
    // WhatsApp status monitoring
    const whatsAppStatus = useWhatsAppStatus()
    const isMonitoringWhatsApp = ref(false)
    const currentDeviceId = ref(null)
    
          // QR monitoring integrado con WhatsApp status
      const lastQrHash = ref('')
      const qrUpdateCount = ref(0)
      
      // Actualizar contador cuando cambie el QR
      const updateQrCount = () => {
        qrUpdateCount.value++
      }

    // Callback para cuando cambia el QR
    const handleQrChanged = (newQr) => {
      console.log('🔄 QR actualizado automáticamente')
      internalInitialData.value = { 
        ...internalInitialData.value, 
        qr: newQr 
      }
      updateQrCount()
    }

    // Listener para conexión exitosa de WhatsApp
    const handleWhatsAppConnected = (event) => {
      const { deviceId, device } = event.detail
      
      // Solo procesar si es el dispositivo actual del modal QR
      if (currentDeviceId.value === deviceId && internalModalType.value === 'qr_view') {
        console.log('🎉 WhatsApp conectado exitosamente, cerrando modal QR')
        
        // Mostrar mensaje de éxito temporal
        internalLoadingText.value = '¡Conectado exitosamente!'
        
        // Emitir evento de éxito para actualizar la tabla
        emit('success', {
          actionType: 'view',
          method: 'GET',
          data: { device },
          originalData: internalInitialData.value
        })
        
        // Cerrar el modal después de un breve delay para mostrar el mensaje
        setTimeout(() => {
          handleClose()
        }, 1500)
      }
    }

    const initializeForm = () => {
      Object.keys(formData).forEach(key => {
        delete formData[key]
      })

      if (internalInitialData.value) {
        formFields.value.forEach(field => {
          formData[field.key] = internalInitialData.value[field.key] || (field.type === 'boolean' ? false : '')
        })
      } else {
        formFields.value.forEach(field => {
          formData[field.key] = field.type === 'boolean' ? false : ''
        })
      }
    }

    const handleSubmit = async () => {
      if (loading.value) return

      // En modo view, solo cerrar el modal
      if (internalActionType.value === 'view') {
        handleClose()
        return
      }

      error.value = ''
      loading.value = true

      try {
        let result

        if (internalEndpoint.value) {
          const response = await axios({
            method: internalMethod.value,
            url: internalEndpoint.value,
            data: internalActionType.value === 'form' ? formData : undefined
          })
          result = response.data
        } else {
          result = { data: formData }
        }

        emit('success', {
          actionType: internalActionType.value,
          method: internalMethod.value,
          data: result.data || result,
          originalData: internalInitialData.value
        })
        
        setTimeout(() => {
          handleClose()
        }, 400)
        
      } catch (err) {
        console.error('Error en FormModal:', err)
        if (err.response?.data?.errors) {
          const errors = Object.values(err.response.data.errors).flat()
          error.value = errors.join(', ')
        } else {
          error.value = err.response?.data?.message || err.message || 'Error al procesar la solicitud'
        }
        emit('error', err)
      } finally {
        loading.value = false
      }
    }

    const handleClose = () => {
      if (loading.value) return
      
      // Detener monitoreo de WhatsApp si está activo
      if (isMonitoringWhatsApp.value) {
        console.log('🛑 Deteniendo monitoreo integrado')
        whatsAppStatus.stopMonitoring()
        isMonitoringWhatsApp.value = false
        currentDeviceId.value = null
      }
      
      isOpen.value = false
      error.value = ''
      emit('close')
    }

    watch(() => props.show, (newValue) => {
      isOpen.value = newValue
      if (newValue) {
        initializeForm()
      }
    }, { immediate: true })

    watch(() => internalInitialData.value, () => {
      if (isOpen.value) {
        initializeForm()
      }
    }, { deep: true })

    // Configurar listener para conexión exitosa de WhatsApp
    onMounted(() => {
      window.addEventListener('whatsapp-connected', handleWhatsAppConnected)
    })

    onUnmounted(() => {
      window.removeEventListener('whatsapp-connected', handleWhatsAppConnected)
      // Detener monitoreo si está activo
      if (isMonitoringWhatsApp.value) {
        whatsAppStatus.stopMonitoring()
        isMonitoringWhatsApp.value = false
      }
    })

    // Método público para abrir el modal - Completamente genérico
    const openModal = async (config = {}) => {
      // Cargar metadatos si se proporciona modelName
      if (props.modelName) {
        await loadModalMetadata()
      }
      
      // Configuración requerida
      if (!config.title) {
        console.error('DataModal: title es requerido')
        return
      }
      // Endpoint solo es requerido si NO es modo view
      if (!config.endpoint && config.actionType !== 'view') {
        console.error('DataModal: endpoint es requerido para actionTypes que no sean "view"')
        return
      }

      // Configurar valores por defecto
      const defaultConfig = {
        actionType: 'form',
        method: 'POST',
        item: null,
        submitText: 'Guardar',
        loadingText: 'Procesando...',
        confirmTitle: '¿Estás seguro?',
        confirmMessage: 'Esta acción no se puede deshacer.',
        columns: 2
      }

      const finalConfig = { ...defaultConfig, ...config }

      // Configurar mensaje de confirmación personalizado si incluye nombre del item
      let confirmMessage = finalConfig.confirmMessage
      if (finalConfig.item && finalConfig.item[props.itemDisplayField]) {
        confirmMessage = finalConfig.confirmMessage.replace(
          '{item}', 
          `"${finalConfig.item[props.itemDisplayField]}"`
        )
      }

      // Actualizar estados internos
      internalTitle.value = finalConfig.title
      internalActionType.value = finalConfig.actionType
      internalEndpoint.value = finalConfig.endpoint
      internalMethod.value = finalConfig.method
      internalInitialData.value = finalConfig.item || {}
      internalSubmitText.value = finalConfig.submitText
      internalLoadingText.value = finalConfig.loadingText
      internalConfirmTitle.value = finalConfig.confirmTitle
      internalConfirmMessage.value = confirmMessage
      internalColumns.value = finalConfig.columns
      internalCustomFields.value = finalConfig.customFields || []
      internalModalType.value = finalConfig.modalType || ''

      // Abrir el modal
      isOpen.value = true
      initializeForm()

      // Si es modo view con endpoint, hacer la petición automáticamente
      if (finalConfig.actionType === 'view' && finalConfig.endpoint) {
        console.log('🚀 Modal view con endpoint detectado:', finalConfig.endpoint)
        await loadViewData()
        
        // Si es modal QR, iniciar monitoreo integrado de WhatsApp y QR
        if (finalConfig.modalType === 'qr_view' && finalConfig.item?.id) {
          console.log('🔍 Iniciando monitoreo integrado para dispositivo:', finalConfig.item.id)
          currentDeviceId.value = finalConfig.item.id
          whatsAppStatus.startMonitoring(finalConfig.item.id, handleQrChanged)
          isMonitoringWhatsApp.value = true
        }
      }
    }

    // Cargar datos para modo view con endpoint
    const loadViewData = async () => {
      if (!internalEndpoint.value) return

      loading.value = true
      error.value = ''
      internalLoadingText.value = 'Creando sesión WhatsApp...'

      const maxRetries = 2
      let retryCount = 0

      while (retryCount < maxRetries) {
        try {
          console.log(`🔍 Intento ${retryCount + 1}/${maxRetries} - Cargando datos del endpoint:`, internalEndpoint.value)
          
          if (retryCount > 0) {
            internalLoadingText.value = `Reintentando... (${retryCount + 1}/${maxRetries})`
            // Esperar menos tiempo antes de reintentar
            await new Promise(resolve => setTimeout(resolve, 1000 * retryCount))
          }
          
          const response = await axios({
            method: internalMethod.value,
            url: internalEndpoint.value,
            timeout: 8000 // 8 segundos de timeout (más rápido)
          })

          console.log('📦 Response completa:', response.data)

          if (response.data.success && response.data.data) {
            console.log('✅ Datos recibidos:', response.data.data)
            // Actualizar los datos del modal con la respuesta
            internalInitialData.value = { ...internalInitialData.value, ...response.data.data }
            console.log('📝 Datos actualizados:', internalInitialData.value)
            internalLoadingText.value = 'QR generado exitosamente'
            loading.value = false
            return // Éxito, salir del bucle
          } else {
            console.error('❌ Error en respuesta:', response.data)
            error.value = response.data.message || 'Error al cargar datos'
            internalLoadingText.value = 'Error al obtener QR'
            break // Salir del bucle si hay error en la respuesta
          }
        } catch (err) {
          console.error(`❌ Error en intento ${retryCount + 1}:`, err)
          
          retryCount++
          
          if (retryCount >= maxRetries) {
            // Último intento falló, mostrar error final
            if (err.response?.data?.message) {
              const message = err.response.data.message
              if (message.includes('timeout') || message.includes('timed out')) {
                error.value = 'El servidor WhatsApp está muy ocupado. Intenta de nuevo en unos minutos.'
                internalLoadingText.value = 'Servidor sobrecargado'
              } else if (message.includes('crear sesión')) {
                error.value = 'Error al crear sesión en WhatsApp. Verifica que el servidor esté funcionando.'
                internalLoadingText.value = 'Error al crear sesión'
              } else if (message.includes('conexión')) {
                error.value = 'Error de conexión con el servidor WhatsApp.'
                internalLoadingText.value = 'Error de conexión'
              } else {
                error.value = message
                internalLoadingText.value = 'Error inesperado'
              }
            } else {
              error.value = 'Error al cargar datos después de varios intentos'
              internalLoadingText.value = 'Error persistente'
            }
            break
          } else {
            // Continuar con el siguiente intento
            internalLoadingText.value = `Reintentando... (${retryCount + 1}/${maxRetries})`
          }
        }
      }
      
      loading.value = false
    }

    return {
      // Estado
      isOpen,
      loading,
      error,
      formData,
      
      // Computed
      showForm,
      showView,
      showConfirmation,
      confirmAction,
      formFields,
      viewFields,
      itemDisplayText,
      
      // Internal state (needed for template)
      internalTitle,
      internalActionType,
      internalEndpoint,
      internalMethod,
      internalInitialData,
      internalSubmitText,
      internalLoadingText,
      internalConfirmTitle,
      internalConfirmMessage,
      internalColumns,
      internalCustomFields,
      internalModalType,
      
      // QR monitoring state (needed for template)
      qrUpdateCount,
      

      
      // Dynamic metadata
      dynamicFields,
      modalMetadata,
      metadataLoading,
      
      // Methods
      openModal,
      handleSubmit,
      handleClose,
      loadViewData
    }
  }
}
</script> 