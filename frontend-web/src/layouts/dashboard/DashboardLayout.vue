<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, useRouter } from 'vue-router'

const router = useRouter()
const drawer = ref(true)

const menuItems = [
  { title: 'Organizaciones', icon: 'mdi-domain', to: '/dashboard/organizations' },
  { title: 'Leads', icon: 'mdi-account-group', to: '/dashboard/leads' },
]

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<template>
  <v-app>
    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" width="260" color="primary" dark>
      <!-- Logo -->
      <div class="pa-4">
        <div class="d-flex align-center">
          <v-avatar color="success" size="42" class="mr-3">
            <v-icon color="white">mdi-whatsapp</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold text-white">Admetricas</div>
            <div class="text-caption" style="opacity: 0.7;">WhatsApp Multi-Tenant</div>
          </div>
        </div>
      </div>

      <v-divider class="mb-2" style="opacity: 0.2;"></v-divider>

      <!-- Menu -->
      <v-list nav dense>
        <v-list-item
          v-for="item in menuItems"
          :key="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          class="mb-1 mx-2"
        ></v-list-item>
      </v-list>

      <template v-slot:append>
        <div class="pa-4">
          <v-btn block variant="outlined" color="white" @click="logout" prepend-icon="mdi-logout">
            Cerrar Sesión
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Header -->
    <v-app-bar flat color="white" elevation="1">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-spacer></v-spacer>
      <v-text-field
        density="compact"
        variant="outlined"
        hide-details
        placeholder="Buscar..."
        prepend-inner-icon="mdi-magnify"
        style="max-width: 250px;"
        class="mr-4"
      ></v-text-field>
      <v-btn icon class="mr-2">
        <v-badge color="error" content="3" overlap>
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
      </v-btn>
      <v-avatar color="primary" size="36">
        <span class="text-white text-body-2">AD</span>
      </v-avatar>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="bg-grey-lighten-4">
      <v-container fluid class="pa-6">
        <RouterView />
      </v-container>
    </v-main>
  </v-app>
</template>
