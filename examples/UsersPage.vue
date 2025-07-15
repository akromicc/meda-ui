<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          Gestión de Usuarios
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Administra los usuarios del sistema
        </p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
      >
        <i class="fas fa-plus mr-2"></i>
        Nuevo Usuario
      </button>
    </div>

    <!-- Estadísticas -->
    <MedaStatsGrid>
      <MedaStatCard
        title="Total Usuarios"
        :value="stats?.total_users || 0"
        icon="fas fa-users"
        color="primary"
        :loading="!stats"
      />
      
      <MedaStatCard
        title="Usuarios Activos"
        :value="stats?.active_users || 0"
        icon="fas fa-user-check"
        color="success"
        :loading="!stats"
      />
      
      <MedaStatCard
        title="Administradores"
        :value="stats?.admin_users || 0"
        icon="fas fa-user-shield"
        color="warning"
        :loading="!stats"
      />
      
      <MedaStatCard
        title="Inactivos"
        :value="(stats?.total_users || 0) - (stats?.active_users || 0)"
        icon="fas fa-user-times"
        color="danger"
        :loading="!stats"
      />
    </MedaStatsGrid>

    <!-- Tabla de Usuarios -->
    <MedaDataTable
      ref="dataTable"
      endpoint="/api/users"
      model-name="users"
      @dataLoaded="handleDataLoaded"
      @action="handleAction"
    >
      <!-- Custom cell para el rol -->
      <template #cell-role="{ value }">
        <span
          :class="{
            'inline-flex px-2 py-1 text-xs font-semibold rounded-full': true,
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': value === 'admin',
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': value === 'user',
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': value === 'moderator'
          }"
        >
          {{ getRoleLabel(value) }}
        </span>
      </template>

      <!-- Custom cell para el estado activo -->
      <template #cell-is_active="{ value }">
        <span
          :class="{
            'inline-flex px-2 py-1 text-xs font-semibold rounded-full': true,
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': value,
            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': !value
          }"
        >
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>

      <!-- Custom cell para el email -->
      <template #cell-email="{ value }">
        <a
          :href="`mailto:${value}`"
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
        >
          {{ value }}
        </a>
      </template>

      <!-- Custom cell para la fecha -->
      <template #cell-created_at="{ value }">
        <div class="text-sm">
          <div class="text-gray-900 dark:text-gray-100">
            {{ formatDate(value) }}
          </div>
          <div class="text-gray-500 dark:text-gray-400">
            {{ formatTime(value) }}
          </div>
        </div>
      </template>
    </MedaDataTable>

    <!-- Modal Universal -->
    <MedaDataModal
      ref="dataModal"
      model-name="users"
      @success="handleModalSuccess"
      @error="handleModalError"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import { success, error } from '@/plugins/toast' // Asumiendo que tienes un sistema de toast

export default {
  name: 'UsersPage',
  setup() {
    const dataTable = ref(null)
    const dataModal = ref(null)
    const stats = ref({})

    // Manejar datos cargados
    const handleDataLoaded = (data) => {
      stats.value = data.stats || {}
    }

    // Manejar acciones de la tabla
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
        case 'toggle_status':
          toggleUserStatus(item)
          break
        default:
          console.warn('Acción no manejada:', action)
      }
    }

    // Abrir modal para crear usuario
    const openCreateModal = () => {
      dataModal.value.openModal({
        title: 'Crear Usuario',
        endpoint: '/api/users',
        method: 'POST',
        actionType: 'form',
        submitText: 'Crear',
        loadingText: 'Creando...'
      })
    }

    // Abrir modal para ver usuario
    const openViewModal = (user) => {
      dataModal.value.openModal({
        title: 'Información del Usuario',
        actionType: 'view',
        item: user
      })
    }

    // Abrir modal para editar usuario
    const openEditModal = (user) => {
      dataModal.value.openModal({
        title: 'Editar Usuario',
        endpoint: `/api/users/${user.id}`,
        method: 'PUT',
        actionType: 'form',
        item: user,
        submitText: 'Actualizar',
        loadingText: 'Actualizando...'
      })
    }

    // Abrir modal para eliminar usuario
    const openDeleteModal = (user) => {
      dataModal.value.openModal({
        title: 'Eliminar Usuario',
        endpoint: `/api/users/${user.id}`,
        method: 'DELETE',
        actionType: 'confirm',
        item: user,
        submitText: 'Eliminar',
        loadingText: 'Eliminando...',
        confirmMessage: `¿Estás seguro de que quieres eliminar al usuario "${user.name}"? Esta acción no se puede deshacer.`
      })
    }

    // Cambiar estado del usuario
    const toggleUserStatus = async (user) => {
      try {
        const response = await fetch(`/api/users/${user.id}/toggle-status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        })

        const data = await response.json()

        if (data.success) {
          success('Estado actualizado', data.message)
          dataTable.value.refresh()
        } else {
          error('Error', data.message)
        }
      } catch (err) {
        error('Error', 'No se pudo cambiar el estado del usuario')
        console.error('Error al cambiar estado:', err)
      }
    }

    // Manejar éxito del modal
    const handleModalSuccess = (result) => {
      console.log('Modal success:', result)
      
      if (result.method === 'POST') {
        success('Usuario creado', 'El usuario ha sido creado exitosamente')
      } else if (result.method === 'PUT') {
        success('Usuario actualizado', 'Los cambios han sido guardados')
      } else if (result.method === 'DELETE') {
        success('Usuario eliminado', 'El usuario ha sido eliminado del sistema')
      }
      
      // Actualizar tabla
      if (dataTable.value) {
        dataTable.value.refresh()
      }
    }

    // Manejar errores del modal
    const handleModalError = (errorObj) => {
      console.error('Modal error:', errorObj)
      
      if (errorObj.isModelNotFound) {
        error('Usuario no encontrado', 'El usuario que intentas acceder ya no existe')
        if (dataTable.value) {
          dataTable.value.hardRefresh()
        }
        return
      }
      
      const statusCode = errorObj.response?.status
      const errorMessage = errorObj.response?.data?.message || errorObj.message || 'Error inesperado'
      
      if (statusCode === 422) {
        error('Datos inválidos', 'Por favor revisa los datos ingresados')
      } else if (statusCode === 403) {
        error('Sin permisos', errorMessage)
      } else if (statusCode === 500) {
        error('Error del servidor', 'Ha ocurrido un error interno')
      } else {
        error('Error', errorMessage)
      }
    }

    // Utilidades de formato
    const getRoleLabel = (role) => {
      const labels = {
        admin: 'Administrador',
        user: 'Usuario',
        moderator: 'Moderador'
      }
      return labels[role] || role
    }

    const formatDate = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleDateString('es-ES')
    }

    const formatTime = (date) => {
      if (!date) return ''
      return new Date(date).toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    }

    return {
      // Refs
      dataTable,
      dataModal,
      stats,

      // Métodos principales
      handleDataLoaded,
      handleAction,
      openCreateModal,

      // Métodos de modal
      handleModalSuccess,
      handleModalError,

      // Utilidades
      getRoleLabel,
      formatDate,
      formatTime
    }
  }
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>