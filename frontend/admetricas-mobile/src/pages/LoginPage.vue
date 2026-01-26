<template>
  <q-page class="flex flex-center">
    <q-card class="q-pa-md" style="width: 400px; max-width: 90vw;">
      <q-card-section>
        <div class="text-h6 text-center">Admetricas Login</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit="onSubmit">
          <q-input
            filled
            v-model="email"
            label="Email"
            type="email"
            autofocus
            :rules="[val => !!val || 'Requerido']"
          />

          <q-input
            filled
            v-model="password"
            label="Password"
            type="password"
            class="q-mt-md"
            :rules="[val => !!val || 'Requerido']"
          />

          <div class="row justify-center q-mt-lg">
            <q-btn label="Login" type="submit" color="primary" :loading="loading" />
          </div>
        </q-form>

        <!-- Divider -->
        <div class="row items-center q-my-lg">
          <div class="col">
            <q-separator />
          </div>
          <div class="col-auto q-px-md">
            <span class="text-grey-6">O</span>
          </div>
          <div class="col">
            <q-separator />
          </div>
        </div>

        <!-- WhatsApp Business Signup -->
        <div class="column items-center">
          <div class="row items-center q-mb-sm">
            <q-badge color="purple" label="PREMIUM" class="q-mr-sm" />
            <span class="text-caption text-grey-7">Conecta con WhatsApp Business</span>
          </div>
          <WhatsAppSignupButton />
          <p class="text-caption text-grey-6 text-center q-mt-sm" style="max-width: 300px;">
            Accede a funcionalidades premium con tu cuenta de WhatsApp Business
          </p>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from 'stores/auth-store';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import WhatsAppSignupButton from 'components/WhatsAppSignupButton.vue';


const email = ref('');
const password = ref('');
const loading = ref(false);

const authStore = useAuthStore();
const router = useRouter();
const $q = useQuasar();

const onSubmit = async () => {
  loading.value = true;
  try {
    await authStore.login(email.value, password.value);
    $q.notify({
        type: 'positive',
        message: 'Login Correcto'
    });
    router.push('/');
  } catch (error) {
    console.error(error);
    $q.notify({
      type: 'negative',
      message: 'Credenciales inválidas'
    });
  } finally {
    loading.value = false;
  }
};
</script>
