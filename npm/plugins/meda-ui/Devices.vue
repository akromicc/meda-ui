<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
          Dispositivos
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Gestiona tus dispositivos conectados
        </p>
      </div>
      <div class="mt-4 md:mt-0">
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        >
          <i class="fas fa-plus mr-2"></i>
          Nuevo Dispositivo
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <MedaStatsGrid>
      <MedaStatCard
        icon="fas fa-mobile-alt"
        title="Total Dispositivos"
        :value="stats?.total_devices || 0"
        :loading="!stats"
        color="primary"
      />
      
      <MedaStatCard
        icon="fas fa-check-circle"
        title="Conectados"
        :value="stats?.connected_devices || 0"
        :loading="!stats"
        color="success"
      />
      
      <MedaStatCard
        icon="fas fa-bolt"
        title="Activos"
        :value="stats?.active_devices || 0"
        :loading="!stats"
        color="secondary"
      />
      
      <MedaStatCard
        icon="fas fa-times-circle"
        title="Desconectados"
        :value="(stats?.total_devices || 0) - (stats?.connected_devices || 0)"
        :loading="!stats"
        color="danger"
      />
    </MedaStatsGrid>

    <!-- Data Table -->
    <MedaDataTable
      ref="dataTable"
      endpoint="/api/devices"
      model-name="devices"
      @dataLoaded="handleDataLoaded"
      @action="handleAction"
    >
      <!-- Custom Status Cell -->
      <template #cell-status="{ value }">
        <span
          :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusColor(value)]"
        >
          {{ getStatusLabel(value) }}
        </span>
      </template>

      <!-- Custom Active Cell -->
      <template #cell-is_active="{ value }">
        <span
          :class="{
            'inline-flex px-2 py-1 text-xs font-semibold rounded-full': true,
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': value,
            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200': !value
          }"
        >
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>

      <!-- Custom User Cell -->
      <template #cell-user="{ item }">
        <div class="flex items-center">
          <div class="flex-shrink-0 h-8 w-8">
            <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ item.user?.name?.charAt(0).toUpperCase() }}
              </span>
            </div>
          </div>
          <div class="ml-3">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ item.user?.name }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ item.user?.email }}
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Date Cell -->
      <template #cell-created_at="{ value }">
        <div class="text-sm text-gray-900 dark:text-gray-100">
          {{ formatDate(value) }}
        </div>
      </template>
    </MedaDataTable>

    <!-- Data Modal -->
    <MedaDataModal
      ref="dataModal"
      model-name="devices"
      @close="closeModal"
      @success="handleModalSuccess"
      @error="handleModalError"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { success, error } from '../plugins/toast.js'
import { useDataValidation } from '../composables/useDataValidation.js'

export default {
  name: 'DevicesPage',
  setup() {
    const { t } = useI18n()
    const { validateItemExists, invalidateCache } = useDataValidation()
    
    // Reactive data
    const dataTable = ref(null)
    const dataModal = ref(null)
    const stats = ref({})

    // Methods
    const handleDataLoaded = (data) => {
      stats.value = data.stats || {}
    }

    const handleAction = ({ action, item }) => {
      switch (action) {
        case 'view':
          openViewModal(item)
          break
        case 'edit':
          openEditModal(item)
          break
        case 'delete':
          openDeleteModal(item)
          break
        case 'connect':
          connectDevice(item)
          break
        case 'disconnect':
          disconnectDevice(item)
          break
        case 'view_qr':
          viewQrCode(item)
          break

      }
    }

    const getStatusLabel = (status) => {
      const labels = {
        connected: 'Conectado',
        disconnected: 'Desconectado',
        qr_required: 'QR Requerido',
        connecting: 'Conectando',
        error: 'Error'
      }
      return labels[status] || status
    }

    const getStatusColor = (status) => {
      const colorMap = {
        connected: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        disconnected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        qr_required: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        connecting: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        error: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200'
      }
      return colorMap[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    // Callback para cuando un elemento no existe
    const onItemNotFound = (item) => {
      invalidateCache('/api/devices', item)
      if (dataTable.value) {
        dataTable.value.hardRefresh()
      }
    }

    // Modal methods
    const openCreateModal = () => {
      dataModal.value.openModal({
        title: 'Crear Dispositivo',
        endpoint: '/api/devices',
        method: 'POST',
        actionType: 'form',
        submitText: 'Crear',
        loadingText: 'Creando...'
      })
    }

    const openViewModal = (item) => {
      dataModal.value.openModal({
        title: 'Ver Dispositivo',
        actionType: 'view',
        item: item
      })
    }

    const openEditModal = async (item) => {
      if (!(await validateItemExists('/api/devices', item, onItemNotFound))) {
        return
      }
      
      dataModal.value.openModal({
        title: 'Editar Dispositivo',
        endpoint: `/api/devices/${item.id}`,
        method: 'PUT',
        actionType: 'form',
        item: item,
        submitText: 'Actualizar',
        loadingText: 'Actualizando...'
      })
    }

    const openDeleteModal = async (item) => {
      if (!(await validateItemExists('/api/devices', item, onItemNotFound))) {
        return
      }
      
      dataModal.value.openModal({
        title: 'Eliminar Dispositivo',
        endpoint: `/api/devices/${item.id}`,
        method: 'DELETE',
        actionType: 'confirm',
        item: item,
        submitText: 'Eliminar',
        loadingText: 'Eliminando...',
        confirmMessage: `Esta acción eliminará {item} permanentemente. Esta acción no se puede deshacer.`
      })
    }

    const closeModal = () => {
      // El DataModal maneja su propio estado de apertura/cierre
    }

    const handleModalSuccess = async (result) => {
      console.log('Modal success:', result)
      
      if (result.method === 'POST') {
        // Verificar si es una conexión de WhatsApp
        if (result.data?.device?.status === 'qr_required') {
          success('Conexión Iniciada', 'Dispositivo conectado. Mostrando código QR...')
          // Mostrar automáticamente el QR después de conectar
          await viewQrCode(result.data.device)
        } else if (result.data?.device?.status === 'disconnected') {
          success('Dispositivo Desconectado', 'El dispositivo ha sido desconectado de WhatsApp')
        } else {
          success('Dispositivo Creado', 'Tu nuevo dispositivo está listo para usar')
        }
      } else if (result.method === 'PUT') {
        success('Dispositivo Actualizado', 'Los cambios han sido guardados exitosamente')
      } else if (result.method === 'DELETE') {
        success('Dispositivo Eliminado', 'El dispositivo ha sido removido del sistema')
      } else if (result.method === 'GET' && result.actionType === 'view') {
        // Conexión exitosa de WhatsApp desde modal QR
        if (result.data?.device?.status === 'connected') {
          success('¡WhatsApp Conectado!', 'El dispositivo se ha conectado exitosamente a WhatsApp')
        }
      }
      
      // Actualizar datos de la tabla (solo data, no metadata)
      if (dataTable.value) {
        dataTable.value.refresh()
      }
    }

    const handleModalError = (errorObj) => {
      console.error('Modal error:', errorObj)
      
      if (errorObj.isModelNotFound) {
        error('Elemento no encontrado', 'El elemento que intentas acceder ya no existe. Actualizando tabla...')
        
        if (dataTable.value) {
          dataTable.value.hardRefresh()
        }
        return
      }
      
      // Manejar errores específicos de conexión
      const statusCode = errorObj.response?.status
      const errorMessage = errorObj.response?.data?.message || errorObj.message || 'Error inesperado'
      
      if (statusCode === 400) {
        // Error de validación (ya conectado, etc.)
        error('No se puede conectar', errorMessage)
      } else if (statusCode === 500) {
        // Error del servidor
        error('Error del servidor', errorMessage)
      } else {
        error('Error al procesar', errorMessage)
      }
    }

    // Device Actions
    const connectDevice = async (device) => {
      // Usar modal de confirmación si está disponible
      dataModal.value.openModal({
        title: 'Conectar Dispositivo',
        actionType: 'confirm',
        item: device,
        submitText: 'Conectar',
        loadingText: 'Conectando...',
        confirmMessage: '¿Estás seguro de que quieres conectar este dispositivo?',
        endpoint: `/api/devices/${device.id}/connect`,
        method: 'POST'
      })
    }

    const disconnectDevice = async (device) => {
      // Usar modal de confirmación
      dataModal.value.openModal({
        title: 'Desconectar Dispositivo',
        actionType: 'confirm',
        item: device,
        submitText: 'Desconectar',
        loadingText: 'Desconectando...',
        confirmMessage: '¿Estás seguro de que quieres desconectar este dispositivo?',
        endpoint: `/api/devices/${device.id}/disconnect`,
        method: 'POST'
      })
    }

    const viewQrCode = async (device) => {
      // Verificar el estado del dispositivo antes de intentar obtener QR
      if (device.status === 'connected') {
        error('Dispositivo ya conectado', 'Este dispositivo ya está conectado. No necesita código QR.')
        return
      }
      
      if (device.status === 'disconnected') {
        // Si está desconectado, primero intentar conectar
        success('Conectando dispositivo', 'Iniciando conexión...')
        await connectDevice(device)
        return
      }
      
      // Solo mostrar QR si está en estado qr_required o connecting
      if (device.status === 'qr_required' || device.status === 'connecting') {
        dataModal.value.openModal({
          title: 'Código QR',
          actionType: 'view',
          modalType: 'qr_view', // Identificador específico para QR
          endpoint: `/api/devices/${device.id}/qr`,
          method: 'GET',
          item: device, // Pasar el dispositivo completo para tener acceso al ID
          columns: 1
        })
      } else {
        error('Estado incorrecto', 'El dispositivo no está en el estado correcto para mostrar QR. Estado actual: ' + device.status)
      }
    }



    return {
      // Estado
      dataTable,
      dataModal,
      stats,
      
      // Methods
      handleDataLoaded,
      handleAction,
      getStatusLabel,
      getStatusColor,
      formatDate,
      
      // Modal
      openCreateModal,
      closeModal,
      handleModalSuccess,
      handleModalError,
      
      // Device Actions
      connectDevice,
      disconnectDevice,
      viewQrCode
    }
  }
}
</script> 