<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import apiClient from '@/plugins/axios'

const loading = ref(true)
const campaigns = ref<any[]>([])
const errorMsg = ref<string | null>(null)

// Computed stats based on FB Data
const fbStats = computed(() => {
  if (campaigns.value.length === 0) return { spent: 0, clicks: 0, cpm: 0, reach: 0 }
  
  return campaigns.value.reduce((acc, curr) => {
    return {
      spent: acc.spent + (parseFloat(curr.amount_spent) || 0),
      clicks: acc.clicks + (parseInt(curr.clicks) || 0),
      cpm: curr.cpm ? parseFloat(curr.cpm) : acc.cpm, // Simplification
      reach: acc.reach + (parseInt(curr.impressions) || 0)
    }
  }, { spent: 0, clicks: 0, cpm: 0, reach: 0 })
})

const overviewStats = computed(() => [
  { title: 'Inversión Total (Ads)', value: `$${fbStats.value.spent.toFixed(2)}`, icon: 'mdi-currency-usd', color: 'primary', trend: 'Actualizado' },
  { title: 'Clics Obtenidos', value: fbStats.value.clicks.toString(), icon: 'mdi-cursor-default-click-outline', color: 'success', trend: 'Actualizado' },
  { title: 'Costo por Mil (CPM)', value: `$${fbStats.value.cpm.toFixed(2)}`, icon: 'mdi-chart-line', color: 'warning', trend: 'Promedio' },
  { title: 'Impresiones Meta', value: fbStats.value.reach.toString(), icon: 'mdi-eye-outline', color: 'info', trend: 'Actualizado' }
])

const connectFacebook = async () => {
  try {
    loading.value = true
    const response = await apiClient.get('/facebook/login-url')
    if (response.data && response.data.login_url) {
      window.location.href = response.data.login_url
    }
  } catch (error: any) {
    console.error('Error fetching Facebook Login URL:', error)
    errorMsg.value = error.response?.data?.error || 'No se pudo generar el enlace de conexión'
    loading.value = false
  }
}

onMounted(async () => {
  try {
    const response = await apiClient.get('/facebook/campaigns')
    campaigns.value = response.data.data || []
    errorMsg.value = null
  } catch (error: any) {
    console.error('Error fetching dashboard FB data:', error)
    errorMsg.value = error.response?.data?.error || 'No fue posible cargar las métricas de Facebook. Asegurate de conectar tu cuenta.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="main-dashboard">
    <!-- Welcome Header -->
    <div class="d-flex justify-space-between align-center mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">Dashboard Principal</h1>
        <p class="text-body-2 text-medium-emphasis">Resumen general del rendimiento de tus cuentas y métricas clave.</p>
      </div>
      <div>
        <v-btn color="primary" variant="flat" prepend-icon="mdi-download">Exportar Reporte</v-btn>
      </div>
    </div>

    <!-- Alert for Missing Connection -->
    <v-alert
      v-if="errorMsg"
      type="warning"
      variant="tonal"
      class="mb-6"
    >
      <div class="d-flex justify-space-between align-center">
        <span>{{ errorMsg }}</span>
        <v-btn color="primary" variant="flat" size="small" @click="connectFacebook">Conectar con Meta</v-btn>
      </div>
    </v-alert>

    <div v-if="loading" class="d-flex justify-center my-12">
      <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
    </div>

    <div v-else>
      <!-- Quick Stats -->
    <v-row class="mb-6">
      <v-col cols="12" sm="6" lg="3" v-for="(stat, index) in overviewStats" :key="index">
        <v-card class="stat-card" elevation="0">
          <v-card-text class="pa-5">
            <div class="d-flex align-center justify-space-between">
              <div>
                <p class="text-body-2 text-medium-emphasis mb-1">{{ stat.title }}</p>
                <h2 class="text-h4 font-weight-bold">{{ stat.value }}</h2>
                <p class="text-caption font-weight-medium mt-1" :class="`text-${stat.color}`">
                  <v-icon size="small" :color="stat.color" class="mr-1">mdi-trending-up</v-icon>
                  {{ stat.trend }} vs mes anterior
                </p>
              </div>
              <div :class="`stat-icon stat-icon-${stat.color}`">
                <v-icon size="28">{{ stat.icon }}</v-icon>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Area Placeholder -->
    <v-row>
      <!-- Main Chart -->
      <v-col cols="12" md="8">
        <v-card elevation="0" class="chart-card mb-6">
          <v-card-text class="pa-6">
            <div class="d-flex justify-space-between align-center mb-6">
              <h3 class="text-h6 font-weight-bold">Rendimiento General</h3>
              <v-btn variant="outlined" size="small" color="primary">Mensual <v-icon right>mdi-chevron-down</v-icon></v-btn>
            </div>
            
            <div class="chart-placeholder d-flex align-center justify-center bg-grey-lighten-4 rounded-lg" style="height: 300px; border: 2px dashed #e0e0e0;">
              <!-- Here a real chart like ApexCharts would be inserted -->
              <div class="text-center">
                <v-icon size="48" color="grey-lighten-1" class="mb-2">mdi-chart-areaspline</v-icon>
                <p class="text-body-2 text-medium-emphasis">Gráfico de Rendimiento (Área)</p>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Secondary Chart / Info -->
      <v-col cols="12" md="4">
        <v-card elevation="0" class="chart-card h-100">
          <v-card-text class="pa-6">
            <div class="d-flex justify-space-between align-center mb-6">
              <h3 class="text-h6 font-weight-bold">Campañas Activas</h3>
            </div>
            
            <div v-if="campaigns.length === 0" class="text-center pa-8 bg-grey-lighten-4 rounded-lg">
              <v-icon size="48" color="grey-lighten-1" class="mb-2">mdi-google-ads</v-icon>
              <p class="text-body-2 text-medium-emphasis">No hay campañas disponibles</p>
            </div>
            
            <v-list v-else lines="two" class="bg-transparent">
              <v-list-item
                v-for="campaign in campaigns.slice(0, 5)"
                :key="campaign.id"
                class="px-0 border-b"
              >
                <template v-slot:prepend>
                  <v-avatar color="primary-lighten-4" size="40">
                    <v-icon color="primary">mdi-bullhorn-outline</v-icon>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-medium">{{ campaign.name }}</v-list-item-title>
                <v-list-item-subtitle class="mt-1">
                  <v-chip size="x-small" :color="campaign.status === 'ACTIVE' ? 'success' : 'grey'">
                    {{ campaign.status }}
                  </v-chip>
                  <span class="ml-2 text-caption">Gasto: ${{ (parseFloat(campaign.amount_spent) || 0).toFixed(2) }}</span>
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
    </div>
  </div>
</template>

<style scoped>
.main-dashboard {
  max-width: 1400px;
  margin: 0 auto;
}

.stat-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  background: white;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.chart-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  background: white;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-icon-primary {
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1976d2;
}

.stat-icon-success {
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #388e3c;
}

.stat-icon-warning {
  background: linear-gradient(135deg, #fff8e1 0%, #ffecb3 100%);
  color: #f57c00;
}

.stat-icon-info {
  background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);
  color: #00897b;
}
</style>
