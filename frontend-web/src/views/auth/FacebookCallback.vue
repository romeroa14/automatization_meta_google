<template>
  <v-container class="fill-height">
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="6" class="text-center">
        <v-card elevation="2" class="pa-8 rounded-lg">
          <div v-if="loading">
            <v-progress-circular indeterminate color="primary" size="64" class="mb-4"></v-progress-circular>
            <h2 class="text-h5 font-weight-bold">Conectando con Facebook...</h2>
            <p class="text-body-1 text-medium-emphasis">Estamos procesando tu conexión, por favor no cierres esta ventana.</p>
          </div>
          
          <div v-else-if="error">
            <v-icon color="error" size="64" class="mb-4">mdi-alert-circle-outline</v-icon>
            <h2 class="text-h5 font-weight-bold text-error">Error de Conexión</h2>
            <p class="text-body-1 mb-6">{{ errorMessage }}</p>
            <v-btn color="primary" block @click="router.push('/dashboard/main')">Regresar al Dashboard</v-btn>
          </div>
          
          <div v-else>
            <v-icon color="success" size="64" class="mb-4">mdi-check-circle-outline</v-icon>
            <h2 class="text-h5 font-weight-bold text-success">¡Conexión Exitosa!</h2>
            <p class="text-body-1 mb-6">Tu cuenta de Facebook ha sido vinculada correctamente.</p>
            <v-btn color="primary" block @click="router.push('/dashboard/main')">Ir al Dashboard</v-btn>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import apiClient from '@/plugins/axios'

const router = useRouter()
const route = useRoute()
const loading = ref(true)
const error = ref(false)
const errorMessage = ref('')

onMounted(async () => {
  const code = route.query.code as string
  const state = route.query.state as string

  if (!code) {
    error.value = true
    errorMessage.value = 'No se recibió el código de autorización de Facebook.'
    loading.value = false
    return
  }

  try {
    // Enviamos el código al backend para intercambiarlo por un token
    const response = await apiClient.post('/facebook/callback', {
      code,
      state
    })

    if (response.data.success) {
      // Éxito: el backend ya guardó la conexión
      setTimeout(() => {
        router.push('/dashboard/main')
      }, 1500)
    } else {
      throw new Error(response.data.error || 'Error desconocido en el servidor.')
    }
  } catch (err: any) {
    console.error('Error in Facebook callback:', err)
    error.value = true
    errorMessage.value = err.response?.data?.error || err.message || 'Error al procesar la respuesta de Facebook.'
  } finally {
    loading.value = false
  }
})
</script>
