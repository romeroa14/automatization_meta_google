<script setup lang="ts">
import { ref } from 'vue'
import { RouterView, useRouter } from 'vue-router'

const router = useRouter()
const drawer = ref(true)

const menuItems = [
  { title: 'Dashboard', icon: 'mdi-view-dashboard-outline', to: '/dashboard/main' },
  { title: 'Organizaciones', icon: 'mdi-domain', to: '/dashboard/organizations' },
  { title: 'Leads', icon: 'mdi-account-group-outline', to: '/dashboard/leads' },
]

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
  router.push('/login')
}
</script>

<template>
  <v-app>
    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" width="260" color="white" elevation="0" border="right">
      <!-- Logo -->
      <div class="pa-4 pt-6 pb-4">
        <div class="d-flex align-center cursor-pointer" @click="router.push('/dashboard/main')">
          <v-avatar color="primary" size="42" class="mr-3">
            <v-icon color="white">mdi-whatsapp</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold text-primary" style="font-size: 1.1rem !important;">Admetricas</div>
            <div class="text-caption text-medium-emphasis">WhatsApp Multi-Tenant</div>
          </div>
        </div>
      </div>

      <!-- Menu -->
      <v-list nav class="px-3">
        <v-list-item
          v-for="item in menuItems"
          :key="item.title"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          color="primary"
          class="mb-1"
          active-class="bg-primary-lighten-5 text-primary"
        ></v-list-item>
      </v-list>

      <template v-slot:append>
        <div class="pa-5">
          <v-btn block variant="tonal" color="error" @click="logout" prepend-icon="mdi-logout">
            Cerrar Sesión
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Header -->
    <v-app-bar flat color="white" border="bottom">
      <v-app-bar-nav-icon @click="drawer = !drawer" color="medium-emphasis"></v-app-bar-nav-icon>
      <v-spacer></v-spacer>
      <v-text-field
        density="compact"
        variant="solo-filled"
        flat
        hide-details
        placeholder="Buscar..."
        prepend-inner-icon="mdi-magnify"
        style="max-width: 280px;"
        class="mr-4"
        bg-color="grey-lighten-4"
      ></v-text-field>
      <v-btn icon class="mr-2" color="medium-emphasis">
        <v-badge color="error" dot>
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
      </v-btn>
      <div class="mr-4 d-flex align-center cursor-pointer">
        <v-avatar color="primary-lighten-4" size="40">
          <span class="text-primary font-weight-medium text-body-1">AD</span>
        </v-avatar>
      </div>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="bg-grey-lighten-4">
      <v-container fluid class="pa-6">
        <RouterView />
      </v-container>
    </v-main>
  </v-app>
</template>
