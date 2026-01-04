<template>
  <q-page class="flex flex-center column">
    <div class="text-h2 q-mb-md text-primary">Admetricas</div>
    <div class="text-subtitle1 q-mb-xl text-grey-7">Mobile CRM & Marketing</div>

    <div v-if="!authStore.isAuthenticated" class="column q-gutter-md">
       <q-btn to="/login" color="primary" label="Iniciar Sesión" size="lg" icon="login" />
    </div>

    <div v-else class="column q-gutter-md text-center">
       <div class="text-h6">Bienvenido, {{ authStore.user?.name || 'Usuario' }}</div>

       <!-- Facebook Connection Status -->
       <q-card flat bordered class="q-mt-md" style="max-width: 350px">
         <q-card-section>
           <div v-if="facebookStore.isLoading" class="text-center">
             <q-spinner-facebook color="blue" size="40px" />
             <div class="text-caption q-mt-sm">Verificando conexión...</div>
           </div>

           <div v-else-if="facebookStore.isConnected" class="text-center">
             <q-icon name="fab fa-facebook" color="blue" size="32px" />
             <div class="text-subtitle2 q-mt-sm text-positive">
               <q-icon name="check_circle" /> Conectado como {{ facebookStore.connectionData?.facebook_name }}
             </div>
             <div v-if="facebookStore.needsRenewal" class="text-caption text-warning q-mt-xs">
               <q-icon name="warning" /> Token próximo a expirar
             </div>
             <q-btn
               flat
               dense
               color="negative"
               label="Desconectar"
               icon="link_off"
               class="q-mt-sm"
               @click="handleDisconnect"
             />
           </div>

           <div v-else class="text-center">
             <q-btn
               color="blue"
               text-color="white"
               icon="fab fa-facebook"
               label="Conectar con Facebook"
               :loading="facebookStore.isLoading"
               @click="handleFacebookConnect"
             />
             <div class="text-caption text-grey q-mt-sm">
               Conecta tu cuenta para ver tus campañas
             </div>
           </div>
         </q-card-section>
       </q-card>

       <!-- Main Action Buttons -->
       <div class="row q-gutter-md justify-center q-mt-md">
          <q-btn
            to="/campaigns"
            color="secondary"
            label="Ver Campañas"
            icon="campaign"
            stack
            class="my-btn"
            :disable="!facebookStore.isConnected"
          />
          <q-btn to="/leads" color="accent" label="Gestionar Leads" icon="people" stack class="my-btn" />
       </div>

       <div v-if="!facebookStore.isConnected" class="text-caption text-grey-6 q-mt-sm">
         Conecta Facebook para acceder a tus campañas
       </div>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'stores/auth-store';
import { useFacebookStore } from 'stores/facebook-store';

const $q = useQuasar();
const authStore = useAuthStore();
const facebookStore = useFacebookStore();

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await facebookStore.init();
  }
});

async function handleFacebookConnect() {
  try {
    await facebookStore.startOAuthFlow();
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Error al conectar con Facebook',
      caption: error.message,
    });
  }
}

async function handleDisconnect() {
  $q.dialog({
    title: 'Desconectar Facebook',
    message: '¿Estás seguro de que quieres desconectar tu cuenta de Facebook?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await facebookStore.disconnect();
      $q.notify({
        type: 'positive',
        message: 'Cuenta de Facebook desconectada',
      });
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: 'Error al desconectar',
        caption: error.message,
      });
    }
  });
}
</script>

<style scoped>
.my-btn {
  width: 140px;
  height: 100px;
}
</style>
