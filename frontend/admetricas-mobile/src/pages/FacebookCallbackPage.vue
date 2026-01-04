<template>
  <q-page class="flex flex-center column q-pa-md">
    <q-card class="text-center" style="max-width: 400px; width: 100%">
      <q-card-section>
        <q-spinner-facebook
          v-if="isProcessing"
          color="primary"
          size="80px"
        />

        <div v-else-if="error" class="text-negative">
          <q-icon name="error" size="64px" class="q-mb-md" />
          <div class="text-h6 q-mb-md">Error de Conexión</div>
          <div class="text-body2 q-mb-lg">{{ error }}</div>
          <q-btn
            color="primary"
            label="Volver al Inicio"
            to="/"
            icon="home"
          />
        </div>

        <div v-else-if="success" class="text-positive">
          <q-icon name="check_circle" size="64px" class="q-mb-md" />
          <div class="text-h6 q-mb-md">¡Conexión Exitosa!</div>
          <div class="text-body2 q-mb-lg">
            Tu cuenta de Facebook ha sido conectada.
          </div>
          <q-btn
            color="primary"
            label="Continuar"
            to="/campaigns"
            icon="campaign"
          />
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFacebookStore } from 'stores/facebook-store'
import { useAuthStore } from 'stores/auth-store'
import { api } from 'boot/axios'

const route = useRoute()
const router = useRouter()
const facebookStore = useFacebookStore()
const authStore = useAuthStore()

const isProcessing = ref(true)
const error = ref(null)
const success = ref(false)

onMounted(async () => {
  // Verificar y restaurar autenticación antes de procesar callback
  if (!authStore.isAuthenticated) {
    // Intentar restaurar token desde localStorage
    authStore.init()
    
    // Si aún no hay token, redirigir a login
    if (!authStore.isAuthenticated) {
      isProcessing.value = false
      error.value = 'Debes iniciar sesión primero. Redirigiendo...'
      setTimeout(() => {
        router.push('/login')
      }, 2000)
      return
    }
  }

  // Asegurar que el token esté en los headers de axios
  const token = localStorage.getItem('token')
  if (token) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`
    console.log('[FacebookCallback] Token restaurado en headers')
  }

  // Obtener el code de la URL
  const code = route.query.code
  const errorParam = route.query.error

  if (errorParam) {
    isProcessing.value = false
    error.value = route.query.error_description || 'Usuario canceló la autorización'
    return
  }

  if (!code) {
    isProcessing.value = false
    error.value = 'No se recibió código de autorización'
    return
  }

  try {
    // Enviar code al backend
    await facebookStore.handleCallback(code)
    success.value = true

    // Redirigir después de 2 segundos
    setTimeout(() => {
      router.push('/campaigns')
    }, 2000)

  } catch (err) {
    console.error('[FacebookCallback] Error:', err)
    error.value = err.response?.data?.error || err.message || 'Error procesando autenticación'
    
    // Si es error 401, puede ser que el token haya expirado
    if (err.response?.status === 401) {
      error.value = 'Sesión expirada. Por favor, inicia sesión de nuevo.'
      setTimeout(() => {
        router.push('/login')
      }, 3000)
    }
  } finally {
    isProcessing.value = false
  }
})
</script>

<style scoped>
.q-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}
</style>
