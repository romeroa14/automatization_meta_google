<template>
  <div class="whatsapp-signup-container">
    <q-btn
      :loading="loading"
      :disable="loading || !sdkLoaded"
      color="positive"
      icon="img:https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
      :label="buttonLabel"
      size="md"
      class="whatsapp-signup-btn"
      @click="launchWhatsAppSignup"
    >
      <template v-slot:loading>
        <q-spinner-facebook color="white" />
      </template>
    </q-btn>
    
    <p v-if="!sdkLoaded" class="text-caption text-grey-6 q-mt-sm">
      Cargando SDK de Facebook...
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from 'stores/auth-store';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { api } from 'boot/axios';

// Props
interface Props {
  buttonLabel?: string;
  redirectAfterSuccess?: string;
}

const props = withDefaults(defineProps<Props>(), {
  buttonLabel: 'Conectar WhatsApp Business',
  redirectAfterSuccess: '/'
});

// Composables
const authStore = useAuthStore();
const router = useRouter();
const $q = useQuasar();

// State
const loading = ref(false);
const sdkLoaded = ref(false);
const configId = ref<string>('');
const appId = ref<string>('');

// Declarar tipos para Facebook SDK
declare global {
  interface Window {
    FB: any;
    fbAsyncInit: () => void;
  }
}

/**
 * Cargar configuración desde backend
 */
const loadConfig = async () => {
  try {
    const response = await api.get('/whatsapp-signup/config');
    configId.value = response.data.config_id;
    appId.value = response.data.app_id;
    console.log('[WhatsApp Signup] Config loaded:', configId.value);
  } catch (error) {
    console.error('[WhatsApp Signup] Error loading config:', error);
    $q.notify({
      type: 'negative',
      message: 'Error al cargar configuración de WhatsApp'
    });
  }
};

/**
 * Cargar SDK de Facebook
 */
const loadFacebookSDK = () => {
  // Si ya existe el SDK, no cargar de nuevo
  if (window.FB) {
    sdkLoaded.value = true;
    return;
  }

  // Crear script tag
  const script = document.createElement('script');
  script.src = 'https://connect.facebook.net/en_US/sdk.js';
  script.async = true;
  script.defer = true;
  script.crossOrigin = 'anonymous';
  
  // Agregar al DOM
  document.body.appendChild(script);

  // Inicializar SDK cuando cargue
  window.fbAsyncInit = function() {
    // Esperar hasta que tengamos el appId
    const checkAppId = setInterval(() => {
      if (appId.value) {
        clearInterval(checkAppId);
        window.FB.init({
          appId: appId.value,
          autoLogAppEvents: true,
          xfbml: true,
          version: 'v24.0'
        });
        
        sdkLoaded.value = true;
        console.log('[WhatsApp Signup] Facebook SDK loaded');
      }
    }, 100);
  };
};

/**
 * Message event listener para capturar datos del flujo
 */
const handleMessage = (event: MessageEvent) => {
  if (!event.origin.endsWith('facebook.com')) return;
  
  try {
    const data = JSON.parse(event.data);
    if (data.type === 'WA_EMBEDDED_SIGNUP') {
      console.log('[WhatsApp Signup] Message event:', data);
      
      if (data.event === 'FINISH' || data.event === 'FINISH_ONLY_WABA' || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
        // Flujo completado exitosamente
        console.log('[WhatsApp Signup] Signup completed:', data.data);
        // Los datos se procesarán en el callback de respuesta
      } else if (data.event === 'CANCEL') {
        // Usuario canceló el flujo
        console.log('[WhatsApp Signup] Signup cancelled at:', data.data?.current_step);
        loading.value = false;
        
        $q.notify({
          type: 'info',
          message: 'Registro de WhatsApp cancelado',
          caption: data.data?.current_step ? `En paso: ${data.data.current_step}` : undefined
        });
      } else if (data.data?.error_message) {
        // Error reportado por el usuario
        console.error('[WhatsApp Signup] Error:', data.data);
        loading.value = false;
        
        $q.notify({
          type: 'negative',
          message: 'Error en registro de WhatsApp',
          caption: data.data.error_message
        });
      }
    }
  } catch (error) {
    // Si no es JSON parseable, podría ser otro tipo de mensaje
    console.log('[WhatsApp Signup] Non-JSON message:', event.data);
  }
};

/**
 * Callback cuando Facebook retorna el código
 */
const fbLoginCallback = async (response: any) => {
  console.log('[WhatsApp Signup] FB Response:', response);
  
  if (response.authResponse && response.authResponse.code) {
    const code = response.authResponse.code;
    console.log('[WhatsApp Signup] Code received:', code);
    
    try {
      // Enviar código al backend para intercambio
      const result = await api.post('/whatsapp-signup/callback', { code });
      
      console.log('[WhatsApp Signup] Backend response:', result.data);
      
      if (result.data.success) {
        // Guardar token y usuario en el store
        authStore.token = result.data.token;
        authStore.user = result.data.user;
        
        localStorage.setItem('token', result.data.token);
        localStorage.setItem('user', JSON.stringify(result.data.user));
        
        // Set default header
        api.defaults.headers.common['Authorization'] = `Bearer ${result.data.token}`;
        
        $q.notify({
          type: 'positive',
          message: '¡WhatsApp Business conectado!',
          caption: 'Tu cuenta ha sido configurada exitosamente'
        });
        
        // Redirigir
        router.push(props.redirectAfterSuccess);
      }
    } catch (error: any) {
      console.error('[WhatsApp Signup] Error processing callback:', error);
      
      $q.notify({
        type: 'negative',
        message: 'Error al conectar WhatsApp Business',
        caption: error.response?.data?.message || error.message
      });
    } finally {
      loading.value = false;
    }
  } else {
    console.log('[WhatsApp Signup] No auth response:', response);
    loading.value = false;
    
    $q.notify({
      type: 'warning',
      message: 'No se pudo completar el registro',
      caption: 'Intenta nuevamente'
    });
  }
};

/**
 * Lanzar flujo de WhatsApp Signup
 */
const launchWhatsAppSignup = () => {
  if (!window.FB) {
    $q.notify({
      type: 'warning',
      message: 'SDK de Facebook no está cargado',
      caption: 'Espera un momento e intenta nuevamente'
    });
    return;
  }
  
  if (!configId.value) {
    $q.notify({
      type: 'negative',
      message: 'Configuración no disponible',
      caption: 'Contacta al soporte'
    });
    return;
  }
  
  loading.value = true;
  
  // Wrapper síncrono para el callback async
  window.FB.login((response: any) => {
    // Llamar a la función async dentro del wrapper
    fbLoginCallback(response);
  }, {
    config_id: configId.value,
    response_type: 'code',
    override_default_response_type: true,
    extras: {
      setup: {},
    }
  });
};

// Lifecycle
onMounted(() => {
  loadConfig();
  loadFacebookSDK();
  window.addEventListener('message', handleMessage);
});

onBeforeUnmount(() => {
  window.removeEventListener('message', handleMessage);
});
</script>

<style scoped>
.whatsapp-signup-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.whatsapp-signup-btn {
  font-weight: 600;
  min-width: 250px;
}

.whatsapp-signup-btn :deep(.q-icon) {
  width: 24px;
  height: 24px;
}
</style>
