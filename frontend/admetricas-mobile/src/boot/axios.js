import { boot } from 'quasar/wrappers'
import axios from 'axios'

// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
// Configuración de API URL según el entorno
// En desarrollo: usa proxy de Vite (/api se redirige a http://localhost:8001/api)
// En producción: https://admetricas.com/api (puerto 443 por defecto)
const getApiBaseUrl = () => {
  // Verificar si estamos en el navegador
  if (typeof window !== 'undefined') {
    const hostname = window.location.hostname

    // Si estamos en desarrollo (localhost, 127.0.0.1, o IP local)
    // Usar ruta relativa para que el proxy de Vite lo maneje
    if (hostname === 'localhost' || hostname === '127.0.0.1' || hostname.includes('192.168.')) {
      return '/api'  // El proxy de Vite redirige a http://localhost:8001/api
    }

    // Si estamos en producción (app.admetricas.com)
    // Usar el dominio del backend (puerto 443 HTTPS por defecto)
    return 'https://admetricas.com/api'
  }

  // Fallback por defecto (para SSR o casos especiales)
  return 'https://admetricas.com/api'
}

const api = axios.create({ baseURL: getApiBaseUrl() })

// Nota: El header Origin se envía automáticamente por el navegador en peticiones CORS
// No podemos establecerlo manualmente (el navegador lo bloquea por seguridad)

// Log para debugging (siempre activo para verificar en producción)
if (typeof window !== 'undefined') {
  console.log('[Axios] API Base URL:', api.defaults.baseURL)
  console.log('[Axios] Current hostname:', window.location.hostname)
  console.log('[Axios] Origin:', window.location.origin)
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
