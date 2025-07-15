import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

export function useBackendConnection() {
  const isBackendOnline = ref(true)
  const lastCheck = ref(null)
  const checkInterval = ref(null)
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 10
  const isChecking = ref(false)
  
  // Función para verificar si el backend está disponible
  const checkBackendConnection = async () => {
    if (isChecking.value) return
    
    isChecking.value = true
    
    try {
      // Hacer una petición simple al backend
      const response = await axios.get('/api/health-check', {
        timeout: 5000
      })
      
      if (response.status === 200) {
        // Backend está disponible
        if (!isBackendOnline.value) {
          console.log('[Backend] Conexión restaurada')
          isBackendOnline.value = true
          reconnectAttempts.value = 0
        }
        
        lastCheck.value = new Date()
        return true
      }
    } catch (error) {
      console.log('[Backend] Error de conexión:', error.message)
      
      // Backend no disponible
      if (isBackendOnline.value) {
        console.log('[Backend] Conexión perdida con el servidor')
        isBackendOnline.value = false
      }
      
      return false
    } finally {
      isChecking.value = false
    }
  }
  
  // Iniciar monitoreo periódico
  const startMonitoring = () => {
    // Verificar inmediatamente
    checkBackendConnection()
    
    // Configurar verificación periódica
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
    }
    
    checkInterval.value = setInterval(() => {
      checkBackendConnection()
    }, 10000) // Verificar cada 10 segundos
  }
  
  // Detener monitoreo
  const stopMonitoring = () => {
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
      checkInterval.value = null
    }
  }
  
  // Forzar reconexión
  const forceReconnect = async () => {
    console.log('[Backend] Forzando reconexión...')
    return await checkBackendConnection()
  }
  
  // Interceptor de Axios para detectar errores de conexión
  const setupAxiosInterceptor = () => {
    // Interceptor de respuesta
    axios.interceptors.response.use(
      (response) => {
        // Respuesta exitosa - backend disponible
        if (!isBackendOnline.value) {
          isBackendOnline.value = true
          reconnectAttempts.value = 0
          console.log('[Backend] Conexión restaurada via interceptor')
        }
        return response
      },
      (error) => {
        // Error de respuesta - posible backend no disponible
        if (error.code === 'ECONNABORTED' || 
            error.code === 'ERR_NETWORK' ||
            error.message.includes('Network Error') ||
            error.message.includes('timeout')) {
          
          if (isBackendOnline.value) {
            console.log('[Backend] Error de red detectado via interceptor')
            isBackendOnline.value = false
            
            // Iniciar reconexión automática
            startReconnection()
          }
        }
        
        return Promise.reject(error)
      }
    )
  }
  
  // Reconexión automática con backoff exponencial
  const startReconnection = () => {
    if (reconnectAttempts.value >= maxReconnectAttempts) {
      console.log('[Backend] Máximo número de reintentos alcanzado')
      return
    }
    
    reconnectAttempts.value++
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts.value), 30000) // Max 30 segundos
    
    console.log(`[Backend] Reintentando conexión en ${delay}ms (intento ${reconnectAttempts.value}/${maxReconnectAttempts})`)
    
    setTimeout(async () => {
      const connected = await checkBackendConnection()
      
      if (!connected && reconnectAttempts.value < maxReconnectAttempts) {
        startReconnection()
      }
    }, delay)
  }
  
  // Función para probar notificaciones
  const testNotification = async () => {
    if ('Notification' in window) {
      if (Notification.permission === 'granted') {
        new Notification('Prueba de notificación', {
          body: 'Las notificaciones funcionan correctamente',
          icon: '/icons/android/android-launchericon-192-192.png',
          badge: '/icons/android/android-launchericon-72-72.png',
          tag: 'test-notification',
          requireInteraction: false
        })
        return true
      } else {
        const permission = await Notification.requestPermission()
        if (permission === 'granted') {
          return testNotification()
        }
        return false
      }
    }
    return false
  }
  
  // Configurar al montar
  onMounted(() => {
    setupAxiosInterceptor()
    startMonitoring()
  })
  
  // Limpiar al desmontar
  onUnmounted(() => {
    stopMonitoring()
  })
  
  return {
    isBackendOnline,
    lastCheck,
    reconnectAttempts,
    maxReconnectAttempts,
    isChecking,
    checkBackendConnection,
    forceReconnect,
    startMonitoring,
    stopMonitoring,
    testNotification
  }
} 