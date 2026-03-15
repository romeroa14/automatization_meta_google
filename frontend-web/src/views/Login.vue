<template>
  <v-app>
    <v-main>
      <v-container fluid class="fill-height" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
        <v-row justify="center" align="center">
          <v-col cols="12" sm="8" md="6" lg="4">
            <v-card elevation="12" rounded="lg">
              <v-card-text class="pa-8">
                <div class="text-center mb-6">
                  <h1 class="text-h4 font-weight-bold mb-2">🚀 Admetricas</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">WhatsApp Multi-Tenant System</p>
                </div>

                <v-form @submit.prevent="handleLogin">
                  <v-text-field
                    v-model="email"
                    label="Email"
                    prepend-inner-icon="mdi-email"
                    variant="outlined"
                    type="email"
                    required
                    class="mb-3"
                  ></v-text-field>

                  <v-text-field
                    v-model="password"
                    label="Password"
                    prepend-inner-icon="mdi-lock"
                    variant="outlined"
                    type="password"
                    required
                    class="mb-4"
                  ></v-text-field>

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    block
                    :loading="loading"
                  >
                    Iniciar Sesión
                  </v-btn>
                </v-form>

                <div class="text-center mt-4">
                  <v-btn
                    variant="text"
                    color="primary"
                    @click="skipLogin"
                  >
                    Ir al Dashboard (Demo)
                  </v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)

const handleLogin = async () => {
  loading.value = true
  setTimeout(() => {
    loading.value = false
    router.push('/dashboard/organizations')
  }, 1000)
}

const skipLogin = () => {
  router.push('/dashboard/organizations')
}
</script>
