<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">Configuración de Perfil</div>

    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Información de Contacto</div>
            <div class="text-caption text-grey">
              Configura tus datos para la integración con WhatsApp y CRM.
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <q-form @submit="onSubmit" class="q-gutter-md">
              <q-input
                filled
                v-model="form.name"
                label="Nombre Completo"
                disable
                hint="El nombre no se puede cambiar directamente."
              />

              <q-input
                filled
                v-model="form.email"
                label="Correo Electrónico"
                disable
                hint="El correo es tu identificador de cuenta."
              />

              <q-input
                filled
                v-model="form.whatsapp_number"
                label="Número de WhatsApp Business"
                hint="Formato internacional sin símbolos (ej: 584121234567). Este número vinculará tus leads de n8n."
                :rules="[ val => val && val.length > 5 || 'Ingresa un número válido' ]"
              />

              <div class="row justify-end">
                <q-btn label="Guardar Configuración" type="submit" color="primary" :loading="loading" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>
      
      <div class="col-12 col-md-6">
          <q-card class="q-mb-md">
            <q-card-section>
              <div class="text-h6">API Token para n8n</div>
              <div class="text-caption text-grey">
                Usa este token para autenticar tus peticiones desde n8n.
              </div>
            </q-card-section>
            <q-separator />
            <q-card-section>
               <div v-if="generatedToken" class="q-mb-md">
                   <div class="text-weight-bold text-positive q-mb-sm">¡Token Generado Exitosamente!</div>
                   <q-input 
                      filled 
                      readonly 
                      v-model="generatedToken" 
                      label="Tu API Token" 
                      hint="Cópialo ahora. No podrás verlo de nuevo."
                   >
                      <template v-slot:append>
                        <q-btn round dense flat icon="content_copy" @click="copyToken" />
                      </template>
                   </q-input>
               </div>
               
               <div v-else class="text-center q-pa-md">
                   <p class="text-grey-7">Genera un token único para conectar tus flujos de n8n con AdMetricas.</p>
                   <q-btn icon="vpn_key" label="Generar Nuevo Token" color="secondary" @click="generateToken" :loading="generatingToken" />
               </div>
            </q-card-section>
          </q-card>

          <q-card class="bg-blue-1 text-white">
              <q-card-section>
                  <div class="text-h6 text-primary">¿Cómo funciona?</div>
              </q-card-section>
              <q-card-section class="text-black">
                  <p>
                      Al configurar tu <strong>Número de WhatsApp Business</strong>, nuestro sistema podrá identificar automáticamente los leads que provienen de tus campañas.
                  </p>
                  <p>
                      Asegúrate de que tus flujos de automatización (n8n, Make, etc.) envíen este mismo número en el campo <code>business_phone</code> o <code>receiver</code> al crear el lead.
                  </p>
              </q-card-section>
          </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { api } from 'boot/axios';
import { useQuasar } from 'quasar';

const $q = useQuasar();
const loading = ref(false);

const form = ref({
  name: '',
  email: '',
  whatsapp_number: ''
});

onMounted(async () => {
    fetchProfile();
});

async function fetchProfile() {
    try {
        const response = await api.get('/profile');
        if (response.data.success) {
            const user = response.data.user;
            form.value.name = user.name;
            form.value.email = user.email;
            form.value.whatsapp_number = user.whatsapp_number || '';
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
    }
}

async function onSubmit() {
    loading.value = true;
    try {
        const response = await api.post('/profile', {
            whatsapp_number: form.value.whatsapp_number
        });
        
        if (response.data.success) {
            $q.notify({
                type: 'positive',
                message: 'Perfil actualizado correctamente'
            });
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        $q.notify({
            type: 'negative',
            message: error.response?.data?.message || 'Error al actualizar perfil'
        });
    } finally {
        loading.value = false;
    }
}
const generatedToken = ref('');
const generatingToken = ref(false);

// ... existing code ...

async function generateToken() {
    generatingToken.value = true;
    try {
        const response = await api.post('/profile/token');
        if (response.data.success) {
            generatedToken.value = response.data.token;
            $q.notify({
                type: 'positive',
                message: 'Token generado correctamente'
            });
        }
    } catch (error) {
         $q.notify({
            type: 'negative',
            message: 'Error generando token'
        });
    } finally {
        generatingToken.value = false;
    }
}

function copyToken() {
    navigator.clipboard.writeText(generatedToken.value);
    $q.notify({
        type: 'info',
        message: 'Token copiado al portapapeles'
    });
}
</script>
