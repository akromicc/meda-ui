<template>
  <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <i :class="[icon, 'text-2xl', colorClass]"></i>
        </div>
        <div class="ml-5 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
              {{ title }}
            </dt>
            <dd class="text-lg font-medium text-gray-900 dark:text-white mt-1">
              <Skeleton 
                v-if="loading"
                variant="text"
                width="48px"
                height="20px"
              />
              <span v-else>{{ formattedValue }}</span>
            </dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatCard',
  props: {
    icon: {
      type: String,
      required: true,
      default: 'fas fa-chart-bar'
    },
    title: {
      type: String,
      required: true
    },
    value: {
      type: [Number, String],
      default: 0
    },
    loading: {
      type: Boolean,
      default: false
    },
    color: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'purple', 'green', 'blue', 'red', 'yellow', 'indigo', 'pink', 'gray'].includes(value)
    }
  },
  computed: {
    colorClass() {
      const colors = {
        primary: 'text-primary-600',
        secondary: 'text-secondary-600',
        success: 'text-success-600',
        danger: 'text-danger-600',
        warning: 'text-warning-600',
        // Backward compatibility
        purple: 'text-primary-600',
        green: 'text-success-600',
        blue: 'text-secondary-600',
        red: 'text-danger-600',
        yellow: 'text-warning-600',
        indigo: 'text-secondary-600',
        pink: 'text-primary-600',
        gray: 'text-gray-600'
      }
      return colors[this.color] || colors.primary
    },
    formattedValue() {
      if (this.value === null || this.value === undefined) return '0'
      if (typeof this.value === 'number') {
        return this.value.toLocaleString('es-ES')
      }
      return this.value
    }
  }
}
</script> 