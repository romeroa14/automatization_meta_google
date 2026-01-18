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
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from 'stores/auth-store';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';

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
