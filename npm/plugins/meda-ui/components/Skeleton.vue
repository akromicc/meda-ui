<template>
  <div
    :class="[
      'skeleton',
      {
        'skeleton-circle': variant === 'circle',
        'skeleton-text': variant === 'text',
        'skeleton-rect': variant === 'rect',
        'animate-pulse': animate
      },
      customClass
    ]"
    :style="styleObject"
  />
</template>

<script>
export default {
  name: 'Skeleton',
  props: {
    // Tipo de skeleton
    variant: {
      type: String,
      default: 'rect',
      validator: value => ['rect', 'circle', 'text'].includes(value)
    },
    
    // Ancho
    width: {
      type: [String, Number],
      default: '100%'
    },
    
    // Alto
    height: {
      type: [String, Number],
      default: '1rem'
    },
    
    // Clases CSS adicionales
    customClass: {
      type: String,
      default: ''
    },
    
    // Activar animación pulse adicional
    animate: {
      type: Boolean,
      default: false
    }
  },
  
  computed: {
    styleObject() {
      return {
        width: typeof this.width === 'number' ? `${this.width}px` : this.width,
        height: typeof this.height === 'number' ? `${this.height}px` : this.height
      }
    }
  }
}
</script>

<style>
.skeleton {
  background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: skeleton-loading 1.2s infinite linear;
  border-radius: 0.25rem; /* rounded por defecto */
}

.skeleton-circle {
  border-radius: 9999px;
}

.skeleton-text {
  border-radius: 0.125rem; /* rounded-sm para texto */
  height: 1rem;
}

.skeleton-rect {
  border-radius: 0.375rem; /* rounded-md para rectángulos */
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Dark mode support - usando el mismo método que funciona en style.css */
.dark .skeleton {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200% 100%;
}
</style> 