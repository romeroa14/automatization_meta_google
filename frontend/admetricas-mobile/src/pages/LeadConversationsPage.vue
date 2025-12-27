<template>
  <q-page class="flex column lead-chat-page">
    <!-- Header: WhatsApp Style -->
    <div class="chat-header q-pa-sm bg-grey-2 row items-center justify-between shadow-1">
      <div class="row items-center cursor-pointer" @click="$router.back()">
        <q-btn flat round dense icon="arrow_back" color="grey-8" />
        <q-avatar size="40px" class="q-ml-sm">
           <div class="bg-primary text-white row flex-center full-width full-height text-weight-bold" style="border-radius: 50%">
              {{ leadStore.currentLead?.client_name?.charAt(0).toUpperCase() || '?' }}
           </div>
        </q-avatar>
        <div class="q-ml-md">
          <div class="text-subtitle1 text-weight-bold text-grey-9 q-mb-none lh-120">
            {{ leadStore.currentLead?.client_name || 'Cargando...' }}
          </div>
          <div class="text-caption text-grey-7 row items-center">
             <span v-if="leadStore.currentLead?.intent" class="q-mr-xs text-capitalize">
                {{ leadStore.currentLead.intent }}
             </span>
             <span v-else>En línea</span>
          </div>
        </div>
      </div>
      <div>
         <q-btn flat round dense icon="videocam" color="grey-7" />
         <q-btn flat round dense icon="call" color="grey-7" />
         <q-btn flat round dense icon="more_vert" color="grey-7" />
      </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-area flex-grow-1 q-pa-md scroll relative-position" style="background-color: #e5ddd5; background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.9;">
       <!-- Date Divider Example (Static for now) -->
       <div class="row justify-center q-my-sm">
          <q-badge color="grey-3" text-color="black" label="Hoy" />
       </div>

       <div v-for="conv in leadStore.conversations" :key="conv.id" 
            class="row q-mb-sm" 
            :class="conv.is_client_message ? 'justify-start' : 'justify-end'"
       >
          <div class="chat-bubble shadow-1 relative-position" 
               :class="conv.is_client_message ? 'bg-white' : 'bg-green-1'">
             
             <!-- Message Content -->
             <!-- Mostrar message_text o response (la respuesta del modelo puede estar en response) -->
             <div class="text-body2 text-grey-10 q-pb-xs" style="white-space: pre-wrap;">
               {{ conv.message_text || conv.response || '' }}
             </div>
             
             <!-- Metadata (Time & Ticks) -->
             <div class="row justify-end items-center" style="opacity: 0.7; font-size: 11px;">
                <span class="q-mr-xs">{{ formatDate(conv.timestamp || conv.created_at) }}</span>
                <q-icon v-if="!conv.is_client_message" name="done_all" color="blue" size="14px" />
             </div>

             <!-- Tail SVG (Optional polish) -->
             <!-- <div class="bubble-tail" :class="conv.is_client_message ? 'tail-left' : 'tail-right'"></div> -->
          </div>
       </div>

       <div v-if="!leadStore.conversations.length" class="text-center q-pa-xl text-grey-8">
          <q-icon name="chat_bubble_outline" size="48px" class="q-mb-md" />
          <div>Inicia la conversación con <strong>{{ leadStore.currentLead?.client_name }}</strong></div>
          <div class="text-caption">Los mensajes se sincronizarán con WhatsApp.</div>
       </div>
    </div>

    <!-- AI Suggestion Panel -->
    <div v-if="aiSuggestion" class="bg-amber-1 q-px-md q-py-sm row items-center justify-between border-top-grey">
        <div class="col">
            <div class="text-caption text-amber-9 text-weight-bold row items-center">
                <q-icon name="lightbulb" size="xs" class="q-mr-xs"/> Sugerencia de IA
            </div>
            <div class="text-body2 text-grey-9 ellipsis-2-lines">{{ aiSuggestion }}</div>
        </div>
        <q-btn flat round dense icon="content_copy" color="amber-9" @click="useSuggestion" />
    </div>

    <!-- Input Footer -->
    <div class="chat-footer bg-grey-2 q-pa-sm row items-end">
       <q-btn flat round dense icon="add" color="grey-7" class="q-mb-xs" />
       
       <q-input
          v-model="newMessage"
          borderless
          dense
          bg-color="white"
          rounded
          outlined
          placeholder="Escribe un mensaje"
          class="col q-mx-sm"
          autogrow
          input-class="q-px-sm"
          @keydown.enter.prevent="sendMessage"
       >
          <template v-slot:append>
             <q-icon name="attach_file" class="cursor-pointer" />
             <q-icon v-if="!newMessage" name="camera_alt" class="cursor-pointer q-ml-sm" />
          </template>
       </q-input>

       <q-btn 
          round 
          dense 
          unelevated
          :icon="newMessage ? 'send' : 'mic'" 
          :color="newMessage ? 'primary' : 'teal'" 
          class="q-mb-xs shadow-1"
          @click="sendMessage"
       />
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { onMounted, ref, nextTick, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useLeadStore } from 'stores/lead-store';
import { date, useQuasar } from 'quasar';

const route = useRoute();
const leadStore = useLeadStore();
const $q = useQuasar();

const newMessage = ref('');

const aiSuggestion = computed(() => {
    if (!leadStore.conversations.length) return null;
    const lastMsg = leadStore.conversations[leadStore.conversations.length - 1];
    // Show suggestion only if it's a client message and has a response (suggestion)
    // AND if we haven't replied yet (naive check: last message is from client)
    if (lastMsg.is_client_message && lastMsg.response) {
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
    scrollToBottom();
});

const formatDate = (val: string) => {
    return date.formatDate(val, 'HH:mm');
};

const scrollToBottom = () => {
    nextTick(() => {
        const scrollArea = document.querySelector('.chat-area');
        if (scrollArea) {
            scrollArea.scrollTop = scrollArea.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    
    const leadId = route.params.id as string;
    const msg = newMessage.value; // Store value before clearing
    newMessage.value = '';

    // Optimistic UI update
    leadStore.conversations.push({
        id: Date.now(),
        message_text: msg,
        is_client_message: false,
        created_at: new Date().toISOString(),
        user_id: 'me',
        status: 'sending' 
    });
    
    scrollToBottom();

    // Call Store Action
    // await leadStore.sendMessage(leadId, msg); 
};
</script>

<style scoped>
.lead-chat-page {
    height: 100vh;
    max-height: 100vh;
    overflow: hidden;
}

.lh-120 {
    line-height: 1.2;
}

.chat-bubble {
    border-radius: 7.5px;
    padding: 6px 9px;
    max-width: 80%;
    position: relative;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
}

.bg-green-1 {
    background-color: #dcf8c6 !important; /* WhatsApp green */
}

/* Scrollbar styling */
.chat-area::-webkit-scrollbar {
  width: 6px;
}
.chat-area::-webkit-scrollbar-track {
  background: transparent; 
}
.chat-area::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.2); 
}
</style>
