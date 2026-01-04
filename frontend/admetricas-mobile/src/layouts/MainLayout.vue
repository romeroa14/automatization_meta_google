<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn flat dense round icon="menu" aria-label="Menu" @click="toggleLeftDrawer" />

        <q-toolbar-title> Admetricas </q-toolbar-title>

        <q-btn flat round icon="logout" @click="logout" v-if="authStore.isAuthenticated" />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item-label header> Essential Links </q-item-label>

        <EssentialLink v-for="link in linksList" :key="link.title" v-bind="link" />
        <q-item clickable v-ripple to="/profile" active-class="text-primary">
          <q-item-section avatar>
            <q-icon name="settings" />
          </q-item-section>
          <q-item-section>
            <q-item-label>Configuración</q-item-label>
            <q-item-label caption>Perfil y WhatsApp</q-item-label>
          </q-item-section>
        </q-item>

      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import EssentialLink from 'components/EssentialLink.vue'
import { useAuthStore } from 'stores/auth-store'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const linksList = [
  {
    title: 'Dashboard',
    caption: 'Resumen',
    icon: 'dashboard',
    link: '/',
    internal: true
  },
  {
    title: 'Leads',
    caption: 'CRM & Conversaciones',
    icon: 'people',
    link: '/leads',
    internal: true
  },
  {
    title: 'Campañas',
    caption: 'Marketing Activo',
    icon: 'campaign',
    link: '/campaigns',
    internal: true
  },
  {
    title: 'Kanban',
    caption: 'Tablero de Leads',
    icon: 'view_kanban',
    link: '/kanban',
    internal: true
  },
]

const leftDrawerOpen = ref(false)

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

function logout() {
    authStore.logout()
    router.push('/login')
}
</script>
