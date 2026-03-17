<script setup lang="ts">
import { ref } from 'vue'

const loading = ref(false)

const overviewStats = ref([
  { title: 'Ingresos Mensuales', value: '$12,500', icon: 'mdi-currency-usd', color: 'primary', trend: '+15%' },
  { title: 'Nuevos Leads', value: '342', icon: 'mdi-account-plus-outline', color: 'success', trend: '+5%' },
  { title: 'Tasa de Conversión', value: '18.4%', icon: 'mdi-chart-line', color: 'warning', trend: '+2%' },
  { title: 'Mensajes Enviados', value: '45.2K', icon: 'mdi-message-text-outline', color: 'info', trend: '+10%' }
])
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
        <v-card elevation="0" class="chart-card">
          <v-card-text class="pa-6">
            <div class="d-flex justify-space-between align-center mb-6">
              <h3 class="text-h6 font-weight-bold">Distribución de Leads</h3>
            </div>
            
            <div class="chart-placeholder d-flex align-center justify-center bg-grey-lighten-4 rounded-lg" style="height: 300px; border: 2px dashed #e0e0e0;">
              <!-- Here a real chart like ApexCharts Circular would be inserted -->
              <div class="text-center">
                <v-icon size="48" color="grey-lighten-1" class="mb-2">mdi-chart-donut</v-icon>
                <p class="text-body-2 text-medium-emphasis">Gráfico de Donut</p>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
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
