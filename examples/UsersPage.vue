<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Gestión de Usuarios
      </h1>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Administra los usuarios del sistema con acciones automáticas generadas de los modales
      </p>
    </div>

    <!-- Stats Cards -->
    <StatsGrid 
      :stats="stats" 
      class="mb-6"
    />

    <!-- Data Table with Automatic Actions -->
    <DataTable
      model-name="UserModel"
      endpoint="/api/users"
      :auto-load="true"
      @action="handleAction"
      @success="handleSuccess"
    >
      <!-- Custom cell for status -->
      <template #cell-is_active="{ item, value }">
        <span 
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            value 
              ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
              : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
          ]"
        >
          {{ value ? 'Activo' : 'Inactivo' }}
        </span>
      </template>

      <!-- Custom cell for role -->
      <template #cell-role="{ item, value }">
        <span 
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            value === 'admin' 
              ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400'
              : value === 'moderator'
              ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
              : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
          ]"
        >
          {{ getRoleLabel(value) }}
        </span>
      </template>
    </DataTable>

    <!-- Modal for CRUD operations -->
    <DataModal
      v-model="showModal"
      :model-name="modalModelName"
      :modal-type="modalType"
      :modal-key="modalKey"
      :initial-data="modalData"
      @success="handleModalSuccess"
      @close="handleModalClose"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import DataTable from '../npm/plugins/meda-ui/components/DataTable/index.vue'
import DataModal from '../npm/plugins/meda-ui/components/DataModal.vue'
import StatsGrid from '../npm/plugins/meda-ui/components/StatsGrid.vue'

export default {
  name: 'UsersPage',
  components: {
    DataTable,
    DataModal,
    StatsGrid
  },
  setup() {
    // Modal state
    const showModal = ref(false)
    const modalType = ref('')
    const modalKey = ref('')
    const modalData = ref({})
    const modalModelName = ref('UserModel')

    // Stats data
    const stats = ref([
      {
        title: 'Total Usuarios',
        value: 0,
        change: '+12%',
        changeType: 'positive',
        icon: 'fa fa-users',
        color: 'primary'
      },
      {
        title: 'Usuarios Activos',
        value: 0,
        change: '+5%',
        changeType: 'positive',
        icon: 'fa fa-user-check',
        color: 'success'
      },
      {
        title: 'Administradores',
        value: 0,
        change: '0%',
        changeType: 'neutral',
        icon: 'fa fa-user-shield',
        color: 'warning'
      },
      {
        title: 'Nuevos Hoy',
        value: 0,
        change: '+3',
        changeType: 'positive',
        icon: 'fa fa-user-plus',
        color: 'info'
      }
    ])

    // Load stats
    const loadStats = async () => {
      try {
        const response = await axios.get('/api/users/stats')
        if (response.data.success) {
          stats.value = response.data.stats
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    // Handle table actions (automatically generated from modals)
    const handleAction = ({ action, item }) => {
      console.log('🔧 Action triggered:', action, item)
      
      // Determine modal type based on action
      if (action === 'view') {
        modalType.value = 'view'
        modalKey.value = 'view'
        modalData.value = item
        showModal.value = true
      } else if (action === 'edit') {
        modalType.value = 'edit'
        modalKey.value = 'edit'
        modalData.value = item
        showModal.value = true
      } else if (action === 'delete') {
        modalType.value = 'confirm'
        modalKey.value = 'delete'
        modalData.value = item
        showModal.value = true
      } else if (action === 'toggle_status') {
        // Handle custom action
        handleToggleStatus(item)
      } else if (action === 'duplicate') {
        // Handle custom action
        handleDuplicate(item)
      } else if (action === 'export') {
        // Handle custom action
        handleExport()
      }
    }

    // Handle custom actions
    const handleToggleStatus = async (item) => {
      try {
        const response = await axios.post(`/api/users/${item.id}/toggle-status`)
        if (response.data.success) {
          // Refresh table data
          this.$refs.dataTable?.refresh()
          loadStats()
        }
      } catch (error) {
        console.error('Error toggling status:', error)
      }
    }

    const handleDuplicate = async (item) => {
      try {
        const response = await axios.post(`/api/users/${item.id}/duplicate`)
        if (response.data.success) {
          // Refresh table data
          this.$refs.dataTable?.refresh()
          loadStats()
        }
      } catch (error) {
        console.error('Error duplicating user:', error)
      }
    }

    const handleExport = async () => {
      try {
        const response = await axios.get('/api/users/export', {
          responseType: 'blob'
        })
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'usuarios.csv')
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Error exporting users:', error)
      }
    }

    // Handle modal success
    const handleModalSuccess = (data) => {
      console.log('✅ Modal success:', data)
      showModal.value = false
      
      // Refresh table and stats
      this.$refs.dataTable?.refresh()
      loadStats()
    }

    // Handle modal close
    const handleModalClose = () => {
      showModal.value = false
      modalData.value = {}
    }

    // Handle table success
    const handleSuccess = (data) => {
      console.log('✅ Table success:', data)
    }

    // Helper function to get role label
    const getRoleLabel = (role) => {
      const labels = {
        'admin': 'Administrador',
        'moderator': 'Moderador',
        'user': 'Usuario'
      }
      return labels[role] || role
    }

    // Load initial data
    onMounted(() => {
      loadStats()
    })

    return {
      // State
      showModal,
      modalType,
      modalKey,
      modalData,
      modalModelName,
      stats,

      // Methods
      handleAction,
      handleModalSuccess,
      handleModalClose,
      handleSuccess,
      getRoleLabel,
      loadStats
    }
  }
}
</script>

<style scoped>
/* Custom styles for the page */
</style>