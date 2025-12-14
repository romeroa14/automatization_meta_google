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

const route = useRoute()
const router = useRouter()
const facebookStore = useFacebookStore()

const isProcessing = ref(true)
const error = ref(null)
const success = ref(false)

onMounted(async () => {
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
    error.value = err.message || 'Error procesando autenticación'
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
