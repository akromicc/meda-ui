<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
        @click="handleBackdropClick"
        @keydown.esc="close"
        tabindex="0"
      >
        <Transition
          enter-active-class="duration-300 ease-out"
          enter-from-class="opacity-0 transform scale-95"
          enter-to-class="opacity-100 transform scale-100"
          leave-active-class="duration-200 ease-in"
          leave-from-class="opacity-100 transform scale-100"
          leave-to-class="opacity-0 transform scale-95"
        >
          <div
            v-if="modelValue"
            :class="[
              'relative p-6 border shadow-2xl rounded-xl bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 w-full',
              sizeClasses
            ]"
            @click.stop
          >
            <!-- Header -->
            <header v-if="$slots.header || showCloseButton" class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <slot name="header"></slot>
              </div>
              
              <!-- Close button -->
              <button
                v-if="showCloseButton"
                @click="close"
                class="ml-4 -mt-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 flex-shrink-0"
                :aria-label="closeButtonLabel"
              >
                <i class="fas fa-times text-lg"></i>
              </button>
            </header>

            <!-- Body -->
            <main class="mb-6">
              <slot name="body">
                <slot></slot>
              </slot>
            </main>

            <!-- Footer -->
            <footer v-if="$slots.footer" class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
              <slot name="footer"></slot>
            </footer>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script>
import { onMounted, onUnmounted, watch } from 'vue'

export default {
  name: 'Modal',
  emits: ['update:modelValue', 'close', 'open'],
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
    },
    showCloseButton: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    },
    closeOnEscape: {
      type: Boolean,
      default: true
    },
    closeButtonLabel: {
      type: String,
      default: 'Cerrar modal'
    },
    persistent: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    sizeClasses() {
      const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm', 
        md: 'max-w-md',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        '2xl': 'max-w-6xl',
        full: 'max-w-[95vw]'
      }
      return sizes[this.size] || sizes.md
    }
  },
  setup(props, { emit }) {
    const close = () => {
      if (props.persistent) return
      emit('update:modelValue', false)
      emit('close')
    }

    const open = () => {
      emit('update:modelValue', true)
      emit('open')
    }

    const handleBackdropClick = () => {
      if (props.closeOnBackdrop && !props.persistent) {
        close()
      }
    }

    const handleEscapeKey = (event) => {
      if (props.closeOnEscape && event.key === 'Escape' && props.modelValue && !props.persistent) {
        close()
      }
    }

    onMounted(() => {
      document.addEventListener('keydown', handleEscapeKey)
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleEscapeKey)
      document.body.style.overflow = ''
    })

    // Watch for modelValue changes to handle body scroll
    watch(() => props.modelValue, (newValue) => {
      if (newValue) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    })

    return {
      close,
      open,
      handleBackdropClick
    }
  }
}
</script>

<style scoped>
/* Additional backdrop blur for better effect */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}
</style> 