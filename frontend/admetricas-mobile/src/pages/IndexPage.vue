<template>
  <q-page class="flex flex-center column">
    <div class="text-h2 q-mb-md text-primary">Admetricas</div>
    <div class="text-subtitle1 q-mb-xl text-grey-7">Mobile CRM & Marketing</div>

    <div v-if="!authStore.isAuthenticated" class="column q-gutter-md">
       <q-btn to="/login" color="primary" label="Iniciar Sesión" size="lg" icon="login" />
    </div>

    <div v-else class="column q-gutter-md text-center">
       <div class="text-h6">Bienvenido, {{ authStore.user?.name || 'Usuario' }}</div>
       <div class="row q-gutter-md justify-center q-mt-md">
          <q-btn to="/campaigns" color="secondary" label="Ver Campañas" icon="campaign" stack class="my-btn" />
          <q-btn to="/leads" color="accent" label="Gestionar Leads" icon="people" stack class="my-btn" />
       </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { useAuthStore } from 'stores/auth-store';

const authStore = useAuthStore();
</script>

<style scoped>
.my-btn {
  width: 140px;
  height: 100px;
}
</style>
