import { boot } from 'quasar/wrappers'
import axios from 'axios'

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
// Configuración de API URL según el entorno
// En desarrollo: http://localhost:8000/api
// En producción: https://admetricas.com/api
const getApiBaseUrl = () => {
  // Verificar si estamos en el navegador
  if (typeof window !== 'undefined') {
    const hostname = window.location.hostname
    
    // Si estamos en desarrollo (localhost, 127.0.0.1, o IP local)
    if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('192.168.')) {
      return 'http://localhost:8000/api'
    }
    
    // Si estamos en producción (app.admetricas.com)
    // Usar el dominio del backend
    return 'https://admetricas.com/api'
  }
  
  // Fallback por defecto (para SSR o casos especiales)
  return 'https://admetricas.com/api'
}

const api = axios.create({ baseURL: getApiBaseUrl() })

// Log para debugging (siempre activo para verificar en producción)
if (typeof window !== 'undefined') {
  console.log('[Axios] API Base URL:', api.defaults.baseURL)
  console.log('[Axios] Current hostname:', window.location.hostname)
}

export default boot(({ app }) => {
    // Restore token from localStorage on app boot
    const token = localStorage.getItem('token')
    if (token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`
        console.log('[Axios Boot] Token restored from localStorage')
    }

    // for use inside Vue files (Options API) through this.$axios and this.$api
    app.config.globalProperties.$axios = axios
    // ^ ^ ^ this will allow you to use this.$axios (for Vue Options API form)
    //       so you won't necessarily have to import axios in each vue file

    app.config.globalProperties.$api = api
    // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
    //       so you can easily perform requests against your app's API
})

export { api }
