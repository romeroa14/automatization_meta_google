<template>
  <q-page class="flex column lead-chat-page">
    <!-- Header: WhatsApp Style - Sticky -->
    <div class="chat-header bg-grey-2 shadow-1 sticky-header">
      <div class="row items-center justify-between q-px-sm q-py-xs">
        <div class="row items-center no-wrap">
          <q-btn flat round dense icon="menu" color="grey-8" class="q-mr-xs" @click="toggleLeftDrawer" />
          <q-btn flat round dense icon="arrow_back" color="grey-8" class="q-mr-xs" @click="$router.back()" />
          <q-avatar size="40px" class="q-mr-sm">
             <div class="bg-primary text-white row flex-center full-width full-height text-weight-bold" style="border-radius: 50%">
                {{ ((leadStore.currentLead as any)?.client_name?.charAt(0) || '?').toUpperCase() }}
             </div>
          </q-avatar>
          <div class="col-auto">
            <div class="text-subtitle2 text-weight-bold text-grey-9 q-mb-none lh-120 ellipsis">
              {{ (leadStore.currentLead as any)?.client_name || 'Cargando...' }}
            </div>
            <div class="text-caption text-grey-7">
               <span v-if="(leadStore.currentLead as any)?.intent" class="text-capitalize">
                  {{ (leadStore.currentLead as any)?.intent }}
               </span>
               <span v-else>En línea</span>
            </div>
          </div>
        </div>
        <div class="row items-center q-gutter-xs">
           <q-btn flat round dense icon="videocam" color="grey-7" size="sm" />
           <q-btn flat round dense icon="call" color="grey-7" size="sm" />
           <q-btn flat round dense icon="more_vert" color="grey-7" size="sm" />
        </div>
      </div>
    </div>

    <!-- Chat Area - Scrollable -->
    <q-scroll-area ref="scrollAreaRef" class="chat-area flex-grow-1" :thumb-style="{ width: '6px', borderRadius: '3px' }">
      <div class="q-pa-md chat-content" style="background-color: #e5ddd5; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: contain; min-height: 100%;">
       <!-- Date Divider Example (Static for now) -->
       <div class="row justify-center q-my-sm">
          <q-badge color="grey-3" text-color="black" label="Hoy" />
       </div>

       <template v-for="msg in flattenedMessages" :key="msg.key">
          <!-- DEBUG: Log antes de renderizar -->
          <div style="display: none;">{{ console.log('🎨 [RENDER]', msg.key, 'isClient:', msg.isClient, 'text:', msg.text.substring(0, 30)) }}</div>
          
          <!-- Mensaje del cliente - Burbuja BLANCA, IZQUIERDA -->
          <div v-if="msg.isClient" 
               class="row q-mb-sm justify-start">
             <div class="chat-bubble shadow-1 relative-position chat-bubble-client">
                <div class="text-body2 text-grey-10 q-pb-xs" style="white-space: pre-wrap;" v-html="decodeEscapedText(msg.text)"></div>
                <div class="row justify-end items-center" style="opacity: 0.7; font-size: 11px;">
                   <span class="q-mr-xs">{{ formatDate(msg.timestamp) }}</span>
                </div>
             </div>
          </div>

          <!-- Respuesta del bot - Burbuja VERDE, DERECHA -->
          <div v-else 
               class="row q-mb-sm justify-end">
             <div class="chat-bubble shadow-1 relative-position chat-bubble-bot">
                <div class="text-body2 text-grey-10 q-pb-xs" style="white-space: pre-wrap;" v-html="decodeEscapedText(msg.text)"></div>
                <div class="row justify-end items-center" style="opacity: 0.7; font-size: 11px;">
                   <span class="q-mr-xs">{{ formatDate(msg.timestamp) }}</span>
                   <q-icon name="done_all" color="blue" size="14px" />
                </div>
             </div>
          </div>
       </template>

         <div v-if="!flattenedMessages.length" class="text-center q-pa-xl text-grey-8">
            <q-icon name="chat_bubble_outline" size="48px" class="q-mb-md" />
            <div>Inicia la conversación con <strong>{{ (leadStore.currentLead as any)?.client_name }}</strong></div>
            <div class="text-caption">Los mensajes se sincronizarán con WhatsApp.</div>
         </div>
      </div>
    </q-scroll-area>

    <!-- AI Suggestion Panel -->
    <div v-if="aiSuggestion" class="ai-suggestion-panel bg-amber-1 q-px-md q-py-xs row items-center justify-between">
        <div class="col">
            <div class="text-caption text-amber-9 text-weight-bold row items-center q-mb-xs">
                <q-icon name="lightbulb" size="xs" class="q-mr-xs"/> Sugerencia de IA
            </div>
            <div class="text-body2 text-grey-9 ellipsis-2-lines">{{ aiSuggestion }}</div>
        </div>
        <q-btn flat round dense icon="content_copy" color="amber-9" size="sm" @click="useSuggestion" />
    </div>

    <!-- Input Footer -->
    <div class="chat-footer bg-grey-2 q-pa-xs row items-end no-wrap">
       <q-btn flat round dense icon="add" color="grey-7" size="md" class="q-mr-xs" />
       
       <q-input
          v-model="newMessage"
          borderless
          dense
          bg-color="white"
          rounded
          outlined
          placeholder="Escribe un mensaje"
          class="col"
          autogrow
          input-class="q-px-sm"
          @keydown.enter.prevent="sendMessage"
       >
          <template v-slot:append>
             <q-icon name="attach_file" class="cursor-pointer q-mr-xs" size="sm" />
             <q-icon v-if="!newMessage" name="camera_alt" class="cursor-pointer" size="sm" />
          </template>
       </q-input>

       <q-btn 
          round 
          dense 
          unelevated
          :icon="newMessage ? 'send' : 'mic'" 
          :color="newMessage ? 'primary' : 'teal'" 
          class="q-ml-xs shadow-1"
          size="md"
          @click="sendMessage"
       />
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { onMounted, ref, nextTick, computed, inject } from 'vue';
import { useRoute } from 'vue-router';
import { useLeadStore } from 'stores/lead-store';
import { date, useQuasar } from 'quasar';

const route = useRoute();
const leadStore = useLeadStore();
const $q = useQuasar();

// Inyectar la función toggleLeftDrawer del layout
const toggleLeftDrawer = inject<() => void>('toggleLeftDrawer', () => {});

const newMessage = ref('');

const flattenedMessages = computed(() => {
    const messages: Array<{
        key: string;
        text: string;
        timestamp: Date;
        isClient: boolean;
        conversationId: number;
        order: number;
    }> = [];

    const cleanTimestamp = (ts: string | null): Date => {
        if (!ts) return new Date();
        const cleaned = ts.replace(/^["\\]+|["\\]+$/g, '').replace(/\\"/g, '');
        const parsed = new Date(cleaned);
        return isNaN(parsed.getTime()) ? new Date() : parsed;
    };

    console.log('[DEBUG] conversations raw:', leadStore.conversations);

    leadStore.conversations.forEach((conv: any, index: number) => {
        const baseTimestamp = cleanTimestamp(conv.timestamp) || new Date(conv.created_at);
        
        // Si hay message_text, agregar como mensaje del cliente (BLANCO, IZQUIERDA)
        if (conv.message_text) {
            messages.push({
                key: `${conv.id}-client`,
                text: conv.message_text,
                timestamp: baseTimestamp,
                isClient: true,
                conversationId: conv.id,
                order: index * 2,
            });
        }
        
        // Si hay response, agregar como respuesta del bot (VERDE, DERECHA)
        if (conv.response) {
            const botTimestamp = new Date(baseTimestamp.getTime() + 1);
            messages.push({
                key: `${conv.id}-bot`,
                text: conv.response,
                timestamp: botTimestamp,
                isClient: false,
                conversationId: conv.id,
                order: index * 2 + 1,
            });
        }
    });

    console.log('[DEBUG] flattenedMessages:', messages.map(m => ({ key: m.key, isClient: m.isClient, text: m.text.substring(0, 30) })));

    // Ordenar por order para mantener secuencia cliente -> bot
    return messages.sort((a, b) => a.order - b.order);
});

const aiSuggestion = computed(() => {
    if (!leadStore.conversations.length) return null;
    const lastMsg = leadStore.conversations[leadStore.conversations.length - 1] as any;
    if (lastMsg?.message_text && lastMsg?.response) {
        return lastMsg.response;
    }
    return null;
});

const useSuggestion = () => {
    if (aiSuggestion.value) {
        newMessage.value = aiSuggestion.value;
    }
};

onMounted(async () => {
    const leadId = route.params.id as string;
    await leadStore.fetchLead(leadId);
    await leadStore.fetchConversations(leadId);
    
    // Log para verificar datos recibidos
    console.log('🔍 [FRONTEND] Conversations recibidas:', leadStore.conversations);
    leadStore.conversations.forEach((conv: any) => {
        console.log(`📊 [CONV ${conv.id}]`, {
            has_message_text: !!conv.message_text,
            has_response: !!conv.response,
            message_text: conv.message_text?.substring(0, 50),
            response: conv.response?.substring(0, 50)
        });
    });
    
    scrollToBottom();
    
    // Polling removed as per user request to avoid excessive requests
    // The user can refresh manually or we can rely on websocket/pusher in the future

});

const formatDate = (val: string | Date) => {
    return date.formatDate(val, 'HH:mm');
};

/**
 * Decodificar caracteres escapados en el texto
 * Convierte \n a saltos de línea reales, \u00a1 a caracteres Unicode, etc.
 * También convierte Markdown básico a HTML
 */
const decodeEscapedText = (text: string): string => {
    if (!text) return '';
    
    try {
        // Si el texto ya está decodificado (no tiene escapes), devolverlo tal cual
        if (typeof text !== 'string') {
            text = String(text);
        }
        
        // Decodificar caracteres Unicode primero (\u00a1 -> ¡, \u00bf -> ¿, etc.)
        text = text.replace(/\\u([0-9a-fA-F]{4})/g, (match, hex) => {
            return String.fromCharCode(parseInt(hex, 16));
        });
        
        // Decodificar secuencias de escape JSON
        text = text.replace(/\\n/g, '\n')
                   .replace(/\\t/g, '\t')
                   .replace(/\\r/g, '\r')
                   .replace(/\\"/g, '"')
                   .replace(/\\\\/g, '\\');
        
        // Convertir Markdown básico a HTML (opcional, para mejor visualización)
        // **texto** -> <strong>texto</strong>
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
    } catch (e) {
        console.warn('[LeadConversations] Error decoding text:', e, text);
        return text; // Devolver texto original si hay error
    }
    
    return text;
};

const scrollAreaRef = ref<any>(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollAreaRef.value) {
            const scrollArea = scrollAreaRef.value;
            if (scrollArea && typeof scrollArea.setScrollPosition === 'function') {
                // Obtener la altura del contenido
                const scrollHeight = scrollArea.$el?.querySelector('.q-scrollarea__content')?.scrollHeight || 999999;
                scrollArea.setScrollPosition('vertical', scrollHeight, 300);
            }
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    
    const leadId = route.params.id as string;
    const msg = newMessage.value.trim();
    newMessage.value = '';

    try {
        // Optimistic UI update
        const tempId = Date.now();
        (leadStore.conversations as any[]).push({
            id: tempId,
            message_text: null, // Mensaje del empleado no tiene message_text
            response: msg, // El mensaje del empleado va en response
            created_at: new Date().toISOString(),
            timestamp: new Date().toISOString(),
            status: 'sending'
        });
        
        scrollToBottom();

        // Enviar mensaje a WhatsApp usando la API
        const { api } = await import('boot/axios');
        const token = localStorage.getItem('token');
        
        const response = await api.post('/whatsapp/send', {
            lead_id: parseInt(leadId),
            message: msg,
        }, {
            headers: {
                'Authorization': `Bearer ${token}`,
            },
        });

        if (response.data.success) {
            // Actualizar el mensaje temporal con los datos reales
            const tempIndex = (leadStore.conversations as any[]).findIndex((c: any) => c.id === tempId);
            if (tempIndex !== -1) {
                (leadStore.conversations as any[])[tempIndex] = {
                    id: response.data.conversation_id,
                    message_text: null, // Mensaje del empleado no tiene message_text
                    response: msg, // El mensaje del empleado va en response
                    created_at: new Date().toISOString(),
                    timestamp: new Date().toISOString(),
                    status: 'sent',
                };
            }
            
            // Recargar conversaciones para obtener el mensaje real con el message_id
            await leadStore.fetchConversations(leadId);
            scrollToBottom();
            
            $q.notify({
                type: 'positive',
                message: 'Mensaje enviado correctamente',
                position: 'top',
            });
        }
    } catch (error: any) {
        console.error('Error enviando mensaje:', error);
        
        // Remover el mensaje temporal en caso de error
        const tempIndex = (leadStore.conversations as any[]).findIndex((c: any) => c.status === 'sending');
        if (tempIndex !== -1) {
            (leadStore.conversations as any[]).splice(tempIndex, 1);
        }
        
        $q.notify({
            type: 'negative',
            message: error.response?.data?.error || 'Error al enviar mensaje',
            position: 'top',
        });
    }
};
</script>

<style scoped>
.lead-chat-page {
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin: 0;
    padding: 0;
    position: relative;
}

/* Header - Sticky */
.chat-header {
    flex-shrink: 0;
    min-height: 60px;
    z-index: 10;
    background-color: #f5f5f5;
}

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background-color: #f5f5f5 !important;
    margin: 0;
    padding: 0;
    width: 100%;
    border-top: none;
}

/* Chat Area - Scrollable */
.chat-area {
    flex: 1 1 auto;
    min-height: 0; /* Important for flexbox scrolling */
    overflow: hidden;
}

.chat-content {
    padding-bottom: 20px;
}

/* Chat Bubbles */
.chat-bubble {
    border-radius: 7.5px;
    padding: 6px 9px;
    max-width: 80%;
    position: relative;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
    word-wrap: break-word;
    word-break: break-word;
}

/* Burbuja del cliente - BLANCA, IZQUIERDA */
.chat-bubble-client {
    background-color: #ffffff !important;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Burbuja del bot - VERDE, DERECHA */
.chat-bubble-bot {
    background-color: #dcf8c6 !important; /* WhatsApp green */
}

/* AI Suggestion Panel */
.ai-suggestion-panel {
    flex-shrink: 0;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    max-height: 80px;
}

/* Footer */
.chat-footer {
    flex-shrink: 0;
    min-height: 60px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

/* Utility Classes */
.lh-120 {
    line-height: 1.2;
}

/* Responsive Design */
@media (max-width: 600px) {
    .sticky-header {
        top: 0;
    }
    
    .chat-header {
        min-height: 56px;
    }
    
    .chat-header .text-subtitle2 {
        font-size: 14px;
    }
    
    .chat-bubble {
        max-width: 85%;
        padding: 5px 8px;
    }
    
    .chat-footer {
        min-height: 56px;
        padding: 8px 4px;
    }
    
    .chat-footer .q-btn {
        min-width: 36px;
        height: 36px;
    }
}

@media (min-width: 601px) and (max-width: 1024px) {
    .chat-bubble {
        max-width: 75%;
    }
}

@media (min-width: 1025px) {
    .chat-bubble {
        max-width: 70%;
    }
    
    .lead-chat-page {
        max-width: 1200px;
        margin: 0 auto;
    }
}
</style>
