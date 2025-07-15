// Configuración por defecto de Meda UI
export const defaultConfig = {
  // Configuración de API
  api: {
    baseUrl: '/api',
    timeout: 30000,
    retryAttempts: 3
  },

  // Configuración de tablas
  table: {
    defaultPerPage: 15,
    perPageOptions: [10, 15, 25, 50, 100],
    defaultSort: 'created_at',
    defaultOrder: 'desc',
    searchDebounce: 300,
    loadingDelay: 200
  },

  // Configuración de modales
  modal: {
    defaultSize: 'md',
    closeOnBackdrop: true,
    closeOnEscape: true,
    persistent: false
  },

  // Configuración de filtros
  filters: {
    dateFormat: 'DD/MM/YYYY',
    numberFormat: {
      decimal: ',',
      thousands: '.',
      precision: 2
    },
    searchMinLength: 2,
    searchDebounce: 300
  },

  // Configuración de validación
  validation: {
    showErrors: true,
    errorClass: 'text-red-600 text-sm mt-1',
    successClass: 'text-green-600 text-sm mt-1'
  },

  // Configuración de temas
  theme: {
    primary: 'blue',
    success: 'green',
    warning: 'yellow',
    danger: 'red',
    info: 'blue'
  },

  // Configuración de idiomas (removida - las traducciones vienen del backend)
  i18n: {
    defaultLocale: 'es'
  }
}

// Función para obtener configuración
export function getConfig(userConfig = {}) {
  return {
    ...defaultConfig,
    ...userConfig,
    api: {
      ...defaultConfig.api,
      ...userConfig.api
    },
    table: {
      ...defaultConfig.table,
      ...userConfig.table
    },
    modal: {
      ...defaultConfig.modal,
      ...userConfig.modal
    },
    filters: {
      ...defaultConfig.filters,
      ...userConfig.filters
    },
    validation: {
      ...defaultConfig.validation,
      ...userConfig.validation
    },
    theme: {
      ...defaultConfig.theme,
      ...userConfig.theme
    },
    i18n: {
      ...defaultConfig.i18n,
      ...userConfig.i18n,
      messages: {
        ...defaultConfig.i18n.messages,
        ...userConfig.i18n?.messages
      }
    }
  }
}

// Función para obtener traducción (removida - las traducciones vienen del backend)
export function getTranslation(key, locale = 'es') {
  return key // Las traducciones vienen del backend
}

// Función para formatear fechas
export function formatDate(date, format = 'DD/MM/YYYY') {
  if (!date) return ''
  
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  
  // Implementación básica de formateo
  const day = d.getDate().toString().padStart(2, '0')
  const month = (d.getMonth() + 1).toString().padStart(2, '0')
  const year = d.getFullYear()
  
  return format
    .replace('DD', day)
    .replace('MM', month)
    .replace('YYYY', year)
}

// Función para formatear números
export function formatNumber(number, options = {}) {
  if (number === null || number === undefined) return ''
  
  const config = {
    decimal: ',',
    thousands: '.',
    precision: 2,
    ...options
  }
  
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: config.precision,
    maximumFractionDigits: config.precision
  }).format(number)
}