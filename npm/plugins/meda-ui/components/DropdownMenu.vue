<template>
  <div class="relative">
    <button
      ref="trigger"
      class="rounded-full p-1"
      :class="buttonClass"
      aria-haspopup="true"
      @click.prevent="toggleDropdown"
      :aria-expanded="dropdownOpen"
    >
      <span class="sr-only">{{ buttonLabel }}</span>
      <slot name="trigger">
        <!-- Default: 3 puntos horizontales -->
        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
          <circle cx="4" cy="10" r="1.5" />
          <circle cx="10" cy="10" r="1.5" />
          <circle cx="16" cy="10" r="1.5" />
        </svg>
      </slot>
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
        class="origin-top-right bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden"
        :class="`min-w-${minWidth || '36'}`"
        :style="dropdownPosition"
      >
        <ul
          ref="dropdown"
          @focusin="dropdownOpen = true"
          @focusout="dropdownOpen = false"
        >
          <!-- Slot para contenido personalizado -->
          <slot v-if="$slots.default" />
          
          <!-- Opciones automáticas si se pasan como props -->
          <li v-for="option in options" :key="option.key">
            <button
              v-if="!option.href"
              @click="handleOptionClick(option)"
              :disabled="option.disabled"
              class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/20 flex items-center"
              :class="[
                option.color ? getColorClass(option.color) : 'text-gray-700 dark:text-gray-300',
                option.disabled ? 'opacity-50 cursor-not-allowed' : ''
              ]"
            >
              <i v-if="option.icon" :class="option.icon" class="mr-2 flex-shrink-0"></i>
              {{ option.label }}
            </button>
            
            <a
              v-else
              :href="option.href"
              @click="handleOptionClick(option)"
              class="block px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/20 flex items-center"
              :class="option.color ? getColorClass(option.color) : 'text-gray-700 dark:text-gray-300'"
            >
              <i v-if="option.icon" :class="option.icon" class="mr-2 flex-shrink-0"></i>
              {{ option.label }}
            </a>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'

export default {
  name: 'DropdownMenu',
  props: {
    // Alineación del dropdown
    align: {
      type: String,
      default: 'left',
      validator: value => ['left', 'right'].includes(value)
    },
    
    // Ancho mínimo del dropdown
    minWidth: {
      type: String,
      default: '36'
    },
    
    // Opciones del menú (alternativa a usar slots)
    options: {
      type: Array,
      default: () => []
    },
    
    // Clases CSS para el botón
    buttonClass: {
      type: String,
      default: 'text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400'
    },
    
    // Label para accesibilidad
    buttonLabel: {
      type: String,
      default: 'Menú'
    }
  },
  
  emits: ['option-click'],
  
  setup(props, { emit }) {
    const dropdownOpen = ref(false)
    const trigger = ref(null)
    const dropdown = ref(null)
    
    // Dropdown positioning
    const dropdownPosition = ref({})

    // Manejar click en opción
    const handleOptionClick = (option) => {
      dropdownOpen.value = false
      emit('option-click', option)
    }

    // Obtener clase de color para la opción
    const getColorClass = (color) => {
      const colorMap = {
        primary: 'text-blue-600 dark:text-blue-400',
        success: 'text-green-600 dark:text-green-400', 
        warning: 'text-yellow-600 dark:text-yellow-400',
        danger: 'text-red-600 dark:text-red-400',
        secondary: 'text-gray-600 dark:text-gray-400',
        info: 'text-blue-600 dark:text-blue-400'
      }
      return colorMap[color] || 'text-gray-700 dark:text-gray-300'
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

    // Toggle dropdown with position calculation
    const toggleDropdown = () => {
      dropdownOpen.value = !dropdownOpen.value
      if (dropdownOpen.value) {
        // Immediately calculate position when opening
        nextTick(() => {
          calculateDropdownPosition()
        })
      }
    }

    // Cerrar al hacer click fuera
    const clickHandler = ({ target }) => {
      if (!dropdownOpen.value || dropdown.value?.contains(target) || trigger.value?.contains(target)) return
      dropdownOpen.value = false
    }

    // Cerrar con tecla ESC
    const keyHandler = ({ keyCode }) => {
      if (!dropdownOpen.value || keyCode !== 27) return
      dropdownOpen.value = false
    }

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
      dropdownPosition,
      handleOptionClick,
      getColorClass,
      toggleDropdown
    }
  }
}
</script> 