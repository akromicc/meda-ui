<template>
  <div class="relative inline-flex">
          <button
        ref="trigger"
        class="p-1 rounded-md transition-colors duration-200"
        :class="{
          'text-blue-500 bg-blue-50 dark:bg-blue-900/20': isActive,
          'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300': !isActive
        }"
        aria-haspopup="true"
        @click.prevent="toggleDropdown"
        :aria-expanded="dropdownOpen"
        :title="`Filtrar ${column.label}`"
      >
      <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
      </svg>
    </button>
    
    <transition
      enter-active-class="transition ease-out duration-200 transform"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-out duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-show="dropdownOpen" 
        class="min-w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl overflow-hidden"
        :style="dropdownPosition"
      >
        <div ref="dropdown">
          <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
              Filtrar {{ column.label }}
            </div>
          </div>
          
          <div class="p-3">
            <!-- Text Filter -->
            <div v-if="column.type === 'text' || column.type === 'number'">
              <input
                v-model="localValue"
                :type="column.type"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :placeholder="`Buscar ${column.label.toLowerCase()}...`"
              />
            </div>

            <!-- Select Filter -->
            <div v-else-if="column.type === 'select'">
              <select
                v-model="localValue"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              >
                <option value="">Todos</option>
                <option 
                  v-for="option in column.options" 
                  :key="option.value" 
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>

            <!-- Boolean Filter -->
            <div v-else-if="column.type === 'boolean'">
              <select
                v-model="localValue"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
              >
                <option value="">Todos</option>
                <option value="true">Sí</option>
                <option value="false">No</option>
              </select>
            </div>

            <!-- Date Range Filter -->
            <div v-else-if="column.type === 'date'" class="space-y-2">
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Fecha inicio
                </label>
                <input
                  v-model="dateRange.start"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Fecha fin
                </label>
                <input
                  v-model="dateRange.end"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                />
              </div>
            </div>

            <!-- Numeric Range Filter -->
            <div v-else-if="column.type === 'range'" class="space-y-2">
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Mínimo
                </label>
                <input
                  v-model="numericRange.min"
                  type="number"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                  :placeholder="`Mín ${column.label.toLowerCase()}`"
                />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Máximo
                </label>
                <input
                  v-model="numericRange.max"
                  type="number"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                  :placeholder="`Máx ${column.label.toLowerCase()}`"
                />
              </div>
            </div>

            <!-- Search Filter (NEW) -->
            <div v-else-if="column.type === 'search'" class="space-y-2">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                  :placeholder="`Buscar ${column.label.toLowerCase()}...`"
                  @input="handleSearchInput"
                />
                <div v-if="searchLoading" class="absolute right-3 top-2.5">
                  <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                </div>
              </div>
              
              <!-- Search Results -->
              <div v-if="searchResults.length > 0" class="max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                <div
                  v-for="result in searchResults"
                  :key="result.id"
                  @click.stop="selectSearchResult(result)"
                  class="px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0"
                >
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ result.name }} <span v-if="result.id" class="text-xs text-gray-400 ml-1">#{{ result.id }}</span>
                  </div>
                  <div v-if="result.description" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ result.description }}
                  </div>
                </div>
              </div>
              
              <!-- Selected Value Display -->
              <div v-if="selectedSearchItem" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
                      {{ selectedSearchItem.name }} <span v-if="selectedSearchItem.id" class="text-xs text-blue-400 ml-1">#{{ selectedSearchItem.id }}</span>
                    </div>
                    <div v-if="selectedSearchItem.description" class="text-xs text-blue-600 dark:text-blue-300">
                      {{ selectedSearchItem.description }}
                    </div>
                  </div>
                  <button
                    @click.stop="clearSearchSelection"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Relation Filter -->
            <div v-else-if="column.relation">
              <input
                v-model="localValue"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                :placeholder="`Buscar por ${column.label.toLowerCase()}...`"
              />
            </div>
          </div>

          <!-- Actions -->
          <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/20">
            <div class="flex items-center justify-between">
              <button
                @click="clearFilter"
                class="px-3 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              >
                Limpiar
              </button>
              <button
                @click="applyFilter"
                class="px-3 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-md transition-colors"
              >
                Aplicar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'

export default {
  name: 'ColumnFilter',
  props: {
    column: {
      type: Object,
      required: true
    },
    modelValue: {
      type: [String, Number, Boolean, Object],
      default: null
    },
    align: {
      type: String,
      default: 'left'
    }
  },
  emits: ['update:modelValue', 'apply', 'clear'],
  setup(props, { emit }) {
    const dropdownOpen = ref(false)
    const trigger = ref(null)
    const dropdown = ref(null)
    
    // Local values for different filter types
    const localValue = ref('')
    const dateRange = reactive({ start: '', end: '' })
    const numericRange = reactive({ min: '', max: '' })
    
    // Search-specific state
    const searchQuery = ref('')
    const searchResults = ref([])
    const searchLoading = ref(false)
    const selectedSearchItem = ref(null)
    
    // Dropdown positioning
    const dropdownPosition = ref({})

    // Computed to check if filter is active (based on modelValue, not local values)
    const isActive = computed(() => {
      return props.modelValue && props.modelValue.toString().trim() !== ''
    })

    // Toggle dropdown with immediate position calculation
    const toggleDropdown = () => {
      dropdownOpen.value = !dropdownOpen.value
      if (dropdownOpen.value) {
        // Immediately calculate position when opening
        nextTick(() => {
          calculateDropdownPosition()
        })
      }
    }

    // Initialize values from modelValue
    const initializeValues = () => {
      // Reset all values first
      localValue.value = ''
      dateRange.start = ''
      dateRange.end = ''
      numericRange.min = ''
      numericRange.max = ''
      searchQuery.value = ''
      selectedSearchItem.value = null
      
      if (!props.modelValue) return

      if (props.column.type === 'date' && typeof props.modelValue === 'string') {
        // Parse date range format: "2024-01-01_2024-01-31"
        const parts = props.modelValue.split('_')
        if (parts.length === 2) {
          dateRange.start = parts[0]
          dateRange.end = parts[1]
        }
      } else if (props.column.type === 'range' && typeof props.modelValue === 'string') {
        // Parse numeric range format: "10-50"
        const parts = props.modelValue.split('-')
        if (parts.length === 2) {
          numericRange.min = parts[0]
          numericRange.max = parts[1]
        }
      } else if (props.column.type === 'search' && typeof props.modelValue === 'object') {
        selectedSearchItem.value = props.modelValue
      } else if (props.column.type === 'search' && props.modelValue) {
        // If it's just an ID, we might need to fetch the full object
        // For now, store as ID
        localValue.value = props.modelValue
      } else {
        localValue.value = props.modelValue
      }
    }

    // Calculate dropdown position with retry mechanism
    const calculateDropdownPosition = () => {
      if (!trigger.value || !dropdownOpen.value) return
      
      const updatePosition = () => {
        const rect = trigger.value.getBoundingClientRect()
        
        // Check if the element is properly positioned (not 0,0,0,0)
        if (rect.width > 0 && rect.height > 0) {
          dropdownPosition.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: props.align === 'right' ? 'auto' : `${rect.left}px`,
            right: props.align === 'right' ? `${window.innerWidth - rect.right}px` : 'auto',
            zIndex: 9999
          }
        } else {
          // Retry after a short delay if element not properly rendered
          setTimeout(updatePosition, 10)
        }
      }
      
      updatePosition()
    }

    // Debounce helper
    function debounce(func, delay) {
      let timeoutId
      return function (...args) {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => func.apply(this, args), delay)
      }
    }

    // Handle search input with debouncing
    const handleSearchInput = debounce(async () => {
      if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = []
        return
      }

      if (!props.column.searchEndpoint) {
        console.warn('Column search endpoint not configured')
        return
      }

      searchLoading.value = true
      try {
        const response = await axios.get(props.column.searchEndpoint, {
          params: { search: searchQuery.value }
        })
        
        if (response.data.success) {
          searchResults.value = response.data.data
        }
      } catch (error) {
        console.error('Error searching:', error)
        searchResults.value = []
      } finally {
        searchLoading.value = false
      }
    }, 300)

    // Select a search result
    const selectSearchResult = (result) => {
      selectedSearchItem.value = result
      searchQuery.value = result.name
      searchResults.value = []
      // No cerramos el dropdown aquí, se cierra al aplicar
    }

    // Clear search selection
    const clearSearchSelection = () => {
      selectedSearchItem.value = null
      searchQuery.value = ''
      searchResults.value = []
    }

    // Apply filter
    const applyFilter = () => {
      let value = null

      if (props.column.type === 'date') {
        if (dateRange.start && dateRange.end) {
          value = `${dateRange.start}_${dateRange.end}`
        } else if (dateRange.start || dateRange.end) {
          value = dateRange.start || dateRange.end
        }
      } else if (props.column.type === 'range') {
        if (numericRange.min && numericRange.max) {
          value = `${numericRange.min}-${numericRange.max}`
        } else if (numericRange.min || numericRange.max) {
          value = numericRange.min || numericRange.max
        }
      } else if (props.column.type === 'search') {
        value = selectedSearchItem.value ? selectedSearchItem.value.id : null
      } else {
        value = localValue.value && localValue.value.toString().trim() !== '' ? localValue.value : null
      }

      emit('update:modelValue', value)
      emit('apply', {
        column: props.column.key,
        value: value,
        filterKey: getFilterKey()
      })
      
      dropdownOpen.value = false
    }

    // Clear filter
    const clearFilter = () => {
      localValue.value = ''
      dateRange.start = ''
      dateRange.end = ''
      numericRange.min = ''
      numericRange.max = ''
      clearSearchSelection()

      emit('update:modelValue', null)
      emit('clear', {
        column: props.column.key,
        filterKey: getFilterKey()
      })
      
      dropdownOpen.value = false
    }

    // Get filter key based on column configuration
    const getFilterKey = () => {
      if (props.column.relation && props.column.relation_field && props.column.relation_table && props.column.relation_column) {
        // For relation filters, use format: f_user_id:user.name
        return `f_${props.column.relation_field}:${props.column.relation_table}.${props.column.relation_column}`
      } else if (props.column.key.startsWith('r:')) {
        // Handle existing r: format by converting to new format
        // r:user.name -> f_user_id:user.name (assuming user_id is the foreign key)
        const relationPart = props.column.key.substring(2) // Remove 'r:'
        const [relationTable, relationColumn] = relationPart.split('.')
        const foreignKey = `${relationTable}_id` // Assume standard naming convention
        return `f_${foreignKey}:${relationTable}.${relationColumn}`
      } else if (props.column.type === 'search' && props.column.filterField) {
        // For search filters, use the configured filter field
        return `f_${props.column.filterField}`
      } else {
        return `f_${props.column.key}`
      }
    }

    // Click outside handler
    const clickHandler = ({ target }) => {
      if (!dropdownOpen.value || dropdown.value?.contains(target) || trigger.value?.contains(target)) return
      dropdownOpen.value = false
    }

    // Escape key handler
    const keyHandler = ({ keyCode }) => {
      if (!dropdownOpen.value || keyCode !== 27) return
      dropdownOpen.value = false
    }

    // Watch for modelValue changes
    watch(() => props.modelValue, initializeValues, { immediate: true })
    
    // Watch for dropdown open/close
    watch(dropdownOpen, (isOpen) => {
      if (isOpen) {
        // Calculate position on next tick and then again with a small delay for safety
        nextTick(() => {
          calculateDropdownPosition()
          // Recalculate after a small delay to ensure proper positioning
          setTimeout(() => {
            calculateDropdownPosition()
          }, 50)
        })
      }
    })

    onMounted(() => {
      document.addEventListener('click', clickHandler)
      document.addEventListener('keydown', keyHandler)
      window.addEventListener('scroll', calculateDropdownPosition, true)
      window.addEventListener('resize', calculateDropdownPosition)
      initializeValues()
    })

    onUnmounted(() => {
      document.removeEventListener('click', clickHandler)
      document.removeEventListener('keydown', keyHandler)
      window.removeEventListener('scroll', calculateDropdownPosition, true)
      window.removeEventListener('resize', calculateDropdownPosition)
    })

    return {
      dropdownOpen,
      trigger,
      dropdown,
      localValue,
      dateRange,
      numericRange,
      searchQuery,
      searchResults,
      searchLoading,
      selectedSearchItem,
      isActive,
      dropdownPosition,
      toggleDropdown,
      applyFilter,
      clearFilter,
      handleSearchInput,
      selectSearchResult,
      clearSearchSelection
    }
  }
}
</script>